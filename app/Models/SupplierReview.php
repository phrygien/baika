<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReview extends Model
{
    protected $fillable = [
        'supplier_id', 'customer_id', 'order_id',
        'overall_rating', 'communication_rating', 'shipping_speed_rating',
        'product_accuracy_rating', 'packaging_rating', 'after_sales_rating',
        'comment', 'status',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating'          => 'float',
            'communication_rating'    => 'float',
            'shipping_speed_rating'   => 'float',
            'product_accuracy_rating' => 'float',
            'packaging_rating'        => 'float',
            'after_sales_rating'      => 'float',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
