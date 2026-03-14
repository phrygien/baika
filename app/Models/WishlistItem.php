<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    protected $fillable = [
        'wishlist_id', 'product_id', 'product_variant_id', 'price_at_addition', 'notes',
    ];

    protected function casts(): array
    {
        return ['price_at_addition' => 'float'];
    }

    public function wishlist(): BelongsTo { return $this->belongsTo(Wishlist::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }

    public function hasPriceDropped(): bool
    {
        return $this->product && $this->product->base_price < $this->price_at_addition;
    }
}
