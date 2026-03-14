<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleSupplierRequest extends Model
{
    protected $fillable = [
        'flash_sale_id', 'supplier_id', 'product_id', 'product_variant_id',
        'proposed_flash_price', 'original_price', 'proposed_stock',
        'max_quantity_per_user', 'supplier_notes', 'status',
        'negotiated_flash_price', 'negotiated_stock',
        'admin_notes', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_flash_price'   => 'float',
            'original_price'         => 'float',
            'negotiated_flash_price' => 'float',
            'reviewed_at'            => 'datetime',
            'submitted_at'           => 'datetime',
        ];
    }

    public function flashSale(): BelongsTo { return $this->belongsTo(FlashSale::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
