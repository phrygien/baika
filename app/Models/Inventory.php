<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    protected $fillable = [
        'product_id', 'product_variant_id',
        'quantity_in_stock', 'quantity_reserved',
        'quantity_sold', 'quantity_returned',
        'allow_backorder', 'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'allow_backorder'    => 'boolean',
            'last_restocked_at'  => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function availableQuantity(): int
    {
        return max(0, $this->quantity_in_stock - $this->quantity_reserved);
    }

    public function isInStock(): bool
    {
        return $this->availableQuantity() > 0 || $this->allow_backorder;
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return $this->availableQuantity() <= $threshold && $this->availableQuantity() > 0;
    }

    public function reserve(int $qty): void
    {
        $this->increment('quantity_reserved', $qty);
        $this->movements()->create([
            'type'            => 'reserved',
            'quantity'        => $qty,
            'quantity_before' => $this->quantity_in_stock,
            'quantity_after'  => $this->quantity_in_stock,
        ]);
    }

    public function release(int $qty): void
    {
        $this->decrement('quantity_reserved', $qty);
    }

    public function deduct(int $qty): void
    {
        $before = $this->quantity_in_stock;
        $this->decrement('quantity_in_stock', $qty);
        $this->decrement('quantity_reserved', $qty);
        $this->increment('quantity_sold', $qty);
        $this->movements()->create([
            'type'            => 'out',
            'quantity'        => $qty,
            'quantity_before' => $before,
            'quantity_after'  => $this->fresh()->quantity_in_stock,
        ]);
    }
}
