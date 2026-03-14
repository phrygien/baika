<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'transaction_id', 'type', 'amount',
        'balance_before', 'balance_after', 'description', 'metadata', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'float',
            'balance_before' => 'float',
            'balance_after'  => 'float',
            'metadata'       => 'array',
            'expires_at'     => 'datetime',
        ];
    }

    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
}
