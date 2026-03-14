<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'currency',
        'subtotal', 'discount_amount', 'shipping_estimate', 'total',
        'coupon_id', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'          => 'float',
            'discount_amount'   => 'float',
            'shipping_estimate' => 'float',
            'total'             => 'float',
            'expires_at'        => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function itemCount(): int
    {
        return $this->items->sum('quantity');
    }

    public function recalculate(): void
    {
        $subtotal = $this->items->sum('total_price');
        $this->update([
            'subtotal' => $subtotal,
            'total'    => $subtotal - $this->discount_amount + $this->shipping_estimate,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where(fn($q) => $q->whereNull('expires_at')
                                         ->orWhere('expires_at', '>', now()));
    }
}
