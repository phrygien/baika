<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleAnalytic extends Model
{
    protected $fillable = [
        'flash_sale_id', 'flash_sale_product_id', 'recorded_at',
        'views_snapshot', 'unique_visitors_snapshot', 'active_carts',
        'orders_count', 'units_sold', 'revenue', 'stock_remaining',
        'add_to_cart_rate', 'conversion_rate', 'cart_abandonment_rate',
        'top_countries',
    ];

    protected function casts(): array
    {
        return [
            'revenue'               => 'float',
            'add_to_cart_rate'      => 'float',
            'conversion_rate'       => 'float',
            'cart_abandonment_rate' => 'float',
            'top_countries'         => 'array',
            'recorded_at'           => 'datetime',
        ];
    }

    public function flashSale(): BelongsTo { return $this->belongsTo(FlashSale::class); }
    public function flashSaleProduct(): BelongsTo { return $this->belongsTo(FlashSaleProduct::class); }
}
