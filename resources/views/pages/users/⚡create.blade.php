<?php
use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $first_name = '';

    #[Validate('required|string|max:255')]
    public string $last_name = '';

    #[Validate('required|email|unique:users,email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:20')]
    public string $phone = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    #[Validate('required|string|min:8')]
    public string $password_confirmation = '';

    #[Validate('nullable|in:male,female,other')]
    public string $gender = '';

    #[Validate('nullable|date|before:today')]
    public string $date_of_birth = '';

    #[Validate('required|in:active,inactive,pending,banned')]
    public string $status = 'active';

    #[Validate('array')]
    public array $selectedRoles = [];

    public string $roleSearch = '';

    #[On('open-create-user')]
    public function open(): void
    {
        $this->reset();
        $this->status = 'active';
        $this->resetValidation();
        Flux::modal('create-user')->show();
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->when($this->roleSearch, fn($q) =>
                $q->where('name', 'like', "%{$this->roleSearch}%")
                  ->orWhere('display_name', 'like', "%{$this->roleSearch}%")
            )
            ->orderBy('display_name')
            ->get();
    }

    public function save(): void
    {
        $this->validate();

        $user = User::create([
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'name'          => trim("{$this->first_name} {$this->last_name}"),
            'email'         => $this->email,
            'phone'         => $this->phone ?: null,
            'password'      => Hash::make($this->password),
            'gender'        => $this->gender ?: null,
            'date_of_birth' => $this->date_of_birth ?: null,
            'status'        => $this->status,
        ]);

        // Cast string → int pour assignRoles()
        if (!empty($this->selectedRoles)) {
            $user->assignRoles(
                roles: array_map('intval', $this->selectedRoles),
                assignedBy: auth()->id(),
            );
        }

        $this->reset();
        $this->status = 'active';
        $this->dispatch('user-created');
        Flux::modal('create-user')->close();

        $this->dispatch(
            'notify',
            variant: 'success',
            title: __('User created'),
            message: __('The user has been created successfully.'),
        );
    }
};
?>

<flux:modal name="create-user" class="w-full max-w-2xl">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <flux:heading size="lg">{{ __('Create User') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Add a new user to the system.') }}</flux:text>
        </div>

        {{-- Identité --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:input
                wire:model="first_name"
                label="{{ __('First name') }}"
                placeholder="John"
            />
            <flux:input
                wire:model="last_name"
                label="{{ __('Last name') }}"
                placeholder="Doe"
            />
        </div>

        {{-- Contact --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:input
                wire:model="email"
                type="email"
                label="{{ __('Email') }}"
                placeholder="john@example.com"
            />
            <flux:input
                wire:model="phone"
                label="{{ __('Phone') }}"
                placeholder="+1 234 567 890"
            />
        </div>

        {{-- Password --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:input
                wire:model="password"
                type="password"
                label="{{ __('Password') }}"
                placeholder="••••••••"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                type="password"
                label="{{ __('Confirm password') }}"
                placeholder="••••••••"
                viewable
            />
        </div>

        {{-- Infos optionnelles --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:select
                wire:model="gender"
                label="{{ __('Gender') }}"
                placeholder="{{ __('Select gender') }}"
            >
                <flux:select.option value="">{{ __('Not specified') }}</flux:select.option>
                <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
            </flux:select>

            <flux:input
                wire:model="date_of_birth"
                type="date"
                label="{{ __('Date of birth') }}"
            />
        </div>

        {{-- Status --}}
        <flux:select
            wire:model="status"
            label="{{ __('Status') }}"
        >
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
            <flux:select.option value="banned">{{ __('Banned') }}</flux:select.option>
        </flux:select>

        <flux:separator variant="subtle" />

        {{-- Roles --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">{{ __('Roles') }}</flux:heading>
                @if (count($this->selectedRoles))
                    <flux:badge size="sm" color="blue" inset="top bottom">
                        {{ count($this->selectedRoles) }} {{ __('selected') }}
                    </flux:badge>
                @endif
            </div>

            <flux:input
                wire:model.live.debounce.200ms="roleSearch"
                icon="magnifying-glass"
                placeholder="{{ __('Search roles...') }}"
                size="sm"
            />

            <div class="max-h-44 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                @forelse ($this->roles as $role)
                    <label
                        wire:key="role-{{ $role->id }}"
                        class="flex cursor-pointer items-center gap-3 border-b border-zinc-50 px-4 py-2.5 last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/30"
                    >
                        <flux:checkbox
                            wire:model="selectedRoles"
                            value="{{ $role->id }}"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                {{ $role->display_name }}
                            </p>
                            @if ($role->description)
                                <p class="text-xs text-zinc-400">{{ $role->description }}</p>
                            @endif
                        </div>
                        <flux:badge size="sm" color="zinc" inset="top bottom">
                            {{ $role->name }}
                        </flux:badge>
                    </label>
                @empty
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <flux:icon name="shield-exclamation" class="mb-2 size-6 text-zinc-300" />
                        <p class="text-sm text-zinc-400">{{ __('No roles found.') }}</p>
                    </div>
                @endforelse
            </div>

            @if (count($this->selectedRoles))
                <flux:text size="sm" class="text-zinc-400">
                    {{ __('The first selected role will be set as primary.') }}
                </flux:text>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button wire:click="save" variant="primary">
                {{ __('Create User') }}
            </flux:button>
        </div>

    </div>
</flux:modal>
