<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSaleSlot extends Model
{
    protected $fillable = [
        'flash_sale_id', 'category_id', 'title', 'badge_text',
        'thumbnail_image', 'starts_at', 'ends_at', 'sort_order',
        'is_active', 'products_count', 'sold_count',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function flashSale(): BelongsTo { return $this->belongsTo(FlashSale::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function products(): HasMany { return $this->hasMany(FlashSaleProduct::class); }

    public function isActive(): bool
    {
        return $this->is_active
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
    }
}
