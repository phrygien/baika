<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSalePurchaseLimit extends Model
{
    protected $fillable = [
        'flash_sale_product_id', 'user_id',
        'quantity_purchased', 'quantity_in_cart',
        'first_purchased_at', 'last_purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'first_purchased_at' => 'datetime',
            'last_purchased_at'  => 'datetime',
        ];
    }

    public function flashSaleProduct(): BelongsTo { return $this->belongsTo(FlashSaleProduct::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
