<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'supplier_id', 'code', 'name', 'description', 'discount_type',
        'discount_value', 'minimum_order_amount', 'maximum_discount_amount',
        'usage_limit', 'usage_limit_per_user', 'used_count',
        'applies_to_sale_items', 'first_order_only', 'requires_account',
        'applicable_categories', 'applicable_products',
        'excluded_products', 'excluded_categories',
        'applicable_user_tiers', 'restricted_countries',
        'starts_at', 'expires_at', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value'           => 'float',
            'minimum_order_amount'     => 'float',
            'maximum_discount_amount'  => 'float',
            'applies_to_sale_items'    => 'boolean',
            'first_order_only'         => 'boolean',
            'requires_account'         => 'boolean',
            'applicable_categories'    => 'array',
            'applicable_products'      => 'array',
            'excluded_products'        => 'array',
            'excluded_categories'      => 'array',
            'applicable_user_tiers'    => 'array',
            'restricted_countries'     => 'array',
            'is_active'                => 'boolean',
            'starts_at'                => 'datetime',
            'expires_at'               => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        return true;
    }

    public function usageCountByUser(int $userId): int
    {
        return $this->usages()->where('user_id', $userId)->count();
    }

    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->minimum_order_amount && $orderAmount < $this->minimum_order_amount) return 0;
        $discount = match ($this->discount_type) {
            'fixed'      => $this->discount_value,
            'percentage' => $orderAmount * ($this->discount_value / 100),
            default      => 0,
        };
        if ($this->maximum_discount_amount) {
            $discount = min($discount, $this->maximum_discount_amount);
        }
        return round($discount, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                     ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }
}
