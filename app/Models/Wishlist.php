<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    protected $fillable = ['user_id', 'name', 'is_public', 'share_token'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($wishlist) {
            $wishlist->share_token ??= Str::random(32);
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(WishlistItem::class); }

    public function hasProduct(int $productId): bool
    {
        return $this->items()->where('product_id', $productId)->exists();
    }
}
