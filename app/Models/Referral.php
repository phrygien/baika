<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id', 'referred_id', 'referral_code',
        'status', 'reward_type', 'reward_amount', 'reward_granted_at',
        'qualifying_order_id',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount'     => 'float',
            'reward_granted_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo { return $this->belongsTo(User::class, 'referrer_id'); }
    public function referred(): BelongsTo { return $this->belongsTo(User::class, 'referred_id'); }
    public function qualifyingOrder(): BelongsTo { return $this->belongsTo(Order::class, 'qualifying_order_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
}
