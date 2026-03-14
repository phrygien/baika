<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSaleProduct extends Model
{
    protected $fillable = [
        'flash_sale_id', 'flash_sale_slot_id', 'supplier_id', 'product_id', 'product_variant_id',
        'original_price', 'flash_price', 'discount_percentage',
        'flash_stock_total', 'flash_stock_reserved', 'flash_stock_sold',
        'max_quantity_per_order', 'max_quantity_per_user',
        'status', 'is_featured', 'show_stock_level', 'low_stock_threshold',
        'view_count', 'add_to_cart_count', 'checkout_count', 'waitlist_count',
        'restore_stock_after_sale', 'stock_restored', 'stock_restored_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'original_price'          => 'float',
            'flash_price'             => 'float',
            'discount_percentage'     => 'float',
            'is_featured'             => 'boolean',
            'show_stock_level'        => 'boolean',
            'restore_stock_after_sale'=> 'boolean',
            'stock_restored_at'       => 'datetime',
        ];
    }

    public function flashSale(): BelongsTo { return $this->belongsTo(FlashSale::class); }
    public function slot(): BelongsTo { return $this->belongsTo(FlashSaleSlot::class, 'flash_sale_slot_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function purchaseLimits(): HasMany { return $this->hasMany(FlashSalePurchaseLimit::class); }
    public function waitlists(): HasMany { return $this->hasMany(FlashSaleWaitlist::class)->orderBy('position'); }
    public function cartReservations(): HasMany { return $this->hasMany(FlashSaleCartReservation::class)->where('status', 'active'); }

    public function stockRemaining(): int
    {
        return max(0, $this->flash_stock_total - $this->flash_stock_reserved - $this->flash_stock_sold);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stockRemaining() > 0;
    }

    public function isLowStock(): bool
    {
        $remaining = $this->stockRemaining();
        return $remaining > 0 && $remaining <= $this->low_stock_threshold;
    }

    public function canUserBuy(int $userId, int $qty = 1): bool
    {
        $limit = $this->purchaseLimits()->where('user_id', $userId)->first();
        $purchased = $limit?->quantity_purchased ?? 0;
        $inCart    = $limit?->quantity_in_cart ?? 0;
        $maxUser   = $this->max_quantity_per_user ?? $this->max_quantity_per_order;
        return ($purchased + $inCart + $qty) <= $maxUser;
    }

    public function scopeActive($query) { return $query->where('status', 'active'); }
}
