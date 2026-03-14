<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCredit extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'remaining_amount', 'currency',
        'reason', 'source_type', 'source_id',
        'expires_at', 'used_at', 'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'float',
            'remaining_amount' => 'float',
            'expires_at'       => 'datetime',
            'used_at'          => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function issuedBy(): BelongsTo { return $this->belongsTo(User::class, 'issued_by'); }

    public function isExpired(): bool { return $this->expires_at && $this->expires_at->isPast(); }
    public function isUsable(): bool { return $this->remaining_amount > 0 && ! $this->isExpired(); }

    public function scopeUsable($query) { return $query->where('remaining_amount', '>', 0)
                                                       ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())); }
}
