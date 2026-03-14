<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionProduct extends Model
{
    protected $fillable = ['promotion_id', 'product_id', 'category_id', 'discount_override'];

    protected function casts(): array
    {
        return ['discount_override' => 'float'];
    }

    public function promotion(): BelongsTo { return $this->belongsTo(Promotion::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
