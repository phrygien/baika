<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'supplier_id', 'name', 'slug', 'description',
        'banner_image_desktop', 'banner_image_mobile',
        'type', 'discount_type', 'discount_value',
        'buy_quantity', 'get_quantity', 'get_discount_percentage',
        'volume_tiers', 'minimum_order_amount', 'maximum_discount_amount',
        'starts_at', 'ends_at', 'is_active', 'is_featured', 'sort_order', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value'          => 'float',
            'get_discount_percentage' => 'float',
            'minimum_order_amount'    => 'float',
            'maximum_discount_amount' => 'float',
            'volume_tiers'            => 'array',
            'is_active'               => 'boolean',
            'is_featured'             => 'boolean',
            'starts_at'               => 'datetime',
            'ends_at'                 => 'datetime',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(PromotionProduct::class); }

    public function isActive(): bool
    {
        return $this->is_active
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('starts_at', '<=', now())
                     ->where('ends_at', '>=', now());
    }
}
