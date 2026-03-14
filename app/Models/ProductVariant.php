<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'name', 'price', 'compare_at_price',
        'cost_price', 'weight_kg', 'barcode', 'image', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'            => 'float',
            'compare_at_price' => 'float',
            'cost_price'       => 'float',
            'weight_kg'        => 'float',
            'is_active'        => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'product_variant_attribute_values',
            'product_variant_id',
            'attribute_value_id'
        )->withPivot('attribute_id');
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function effectivePrice(): float
    {
        return $this->price ?? $this->product->base_price;
    }

    public function isInStock(): bool
    {
        return $this->inventory && $this->inventory->quantity_in_stock > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
