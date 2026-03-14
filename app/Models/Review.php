<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'customer_id', 'order_item_id',
        'rating', 'quality_rating', 'value_rating', 'accuracy_rating', 'packaging_rating',
        'title', 'body', 'pros', 'cons',
        'size_feedback', 'would_recommend', 'verified_purchase',
        'supplier_response', 'supplier_responded_at',
        'helpful_count', 'not_helpful_count',
        'status', 'moderated_by', 'moderated_at',
    ];

    protected function casts(): array
    {
        return [
            'rating'                  => 'float',
            'quality_rating'          => 'float',
            'value_rating'            => 'float',
            'accuracy_rating'         => 'float',
            'packaging_rating'        => 'float',
            'pros'                    => 'array',
            'cons'                    => 'array',
            'would_recommend'         => 'boolean',
            'verified_purchase'       => 'boolean',
            'supplier_responded_at'   => 'datetime',
            'moderated_at'            => 'datetime',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class)->withTrashed(); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function media(): HasMany { return $this->hasMany(ReviewMedia::class); }
    public function votes(): HasMany { return $this->hasMany(ReviewVote::class); }
    public function reports(): HasMany { return $this->hasMany(ReviewReport::class); }

    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeVerified($query) { return $query->where('verified_purchase', true); }
    public function scopeWithMedia($query) { return $query->whereHas('media'); }
}
