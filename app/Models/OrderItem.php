<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'supplier_id', 'product_id', 'product_variant_id',
        'product_snapshot', 'quantity',
        'unit_price', 'subtotal', 'discount_amount', 'total',
        'commission_rate', 'commission_amount', 'supplier_revenue',
        'tax_rate', 'tax_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'        => 'float',
            'subtotal'          => 'float',
            'discount_amount'   => 'float',
            'total'             => 'float',
            'commission_rate'   => 'float',
            'commission_amount' => 'float',
            'supplier_revenue'  => 'float',
            'tax_rate'          => 'float',
            'tax_amount'        => 'float',
            'product_snapshot'  => 'array',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function commission(): HasMany { return $this->hasMany(Commission::class); }
    public function returns(): HasMany { return $this->hasMany(ReturnItem::class); }
    public function review(): HasMany { return $this->hasMany(Review::class); }

    public function snapshotName(): string { return $this->product_snapshot['name'] ?? ''; }
    public function snapshotImage(): ?string { return $this->product_snapshot['image'] ?? null; }
}
