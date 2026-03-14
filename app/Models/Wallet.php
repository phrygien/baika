<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Wallet extends Model
{
    protected $fillable = [
        'owner_type', 'owner_id',
        'balance', 'pending_balance', 'reserved_balance',
        'currency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'balance'          => 'float',
            'pending_balance'  => 'float',
            'reserved_balance' => 'float',
            'is_active'        => 'boolean',
        ];
    }

    public function owner(): MorphTo { return $this->morphTo(); }
    public function transactions(): HasMany { return $this->hasMany(WalletTransaction::class)->latest(); }

    public function availableBalance(): float { return $this->balance; }
    public function totalBalance(): float { return $this->balance + $this->pending_balance; }

    public function credit(float $amount, string $type, ?string $description = null): WalletTransaction
    {
        $before = $this->balance;
        $this->increment('balance', $amount);
        return $this->transactions()->create([
            'type'           => $type,
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $before + $amount,
            'description'    => $description,
        ]);
    }

    public function debit(float $amount, string $type, ?string $description = null): WalletTransaction
    {
        if ($this->balance < $amount) throw new \Exception("Insufficient wallet balance");
        $before = $this->balance;
        $this->decrement('balance', $amount);
        return $this->transactions()->create([
            'type'           => $type,
            'amount'         => -$amount,
            'balance_before' => $before,
            'balance_after'  => $before - $amount,
            'description'    => $description,
        ]);
    }
}
