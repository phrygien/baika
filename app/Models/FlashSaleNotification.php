<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleNotification extends Model
{
    protected $fillable = [
        'user_id', 'flash_sale_id', 'flash_sale_product_id', 'category_id',
        'channels', 'notify_type', 'minutes_before',
        'is_active', 'last_notified_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'channels'         => 'array',
            'is_active'        => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function flashSale(): BelongsTo { return $this->belongsTo(FlashSale::class); }
    public function flashSaleProduct(): BelongsTo { return $this->belongsTo(FlashSaleProduct::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
