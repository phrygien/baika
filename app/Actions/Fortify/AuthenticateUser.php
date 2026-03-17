<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

class AuthenticateUser
{
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter,
    ) {}

    public function handle($request, $next)
    {
        $user = User::where(
            Fortify::username(),
            $request->{Fortify::username()},
        )->first();

        // Utilisateur introuvable ou mot de passe incorrect
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->fireFailedEvent($request, $user);
            $this->throwFailedAuthenticationException($request);
        }

        // Utilisateur non actif
        if ($user->status !== "active") {
            $this->fireFailedEvent($request, $user);

            throw ValidationException::withMessages([
                Fortify::username() => __(
                    "Your account is not active. Please contact support.",
                ),
            ]);
        }

        $this->guard->login($user, $request->boolean("remember"));

        return $next($request);
    }

    protected function throwFailedAuthenticationException($request): void
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            Fortify::username() => [trans("auth.failed")],
        ]);
    }

    protected function fireFailedEvent($request, $user = null): void
    {
        event(
            new Failed(config("fortify.guard"), $user, [
                Fortify::username() => $request->{Fortify::username()},
                "password" => $request->password,
            ]),
        );
    }
}
