<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQa extends Model
{
    protected $table = 'product_qa';

    protected $fillable = [
        'product_id', 'user_id', 'order_id',
        'question', 'status', 'is_featured', 'answers_count',
    ];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean'];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function answers(): HasMany { return $this->hasMany(ProductQaAnswer::class, 'question_id')->where('status', 'approved'); }
    public function acceptedAnswer(): HasMany { return $this->answers()->where('is_accepted', true); }

    public function scopePublic($query) { return $query->where('status', 'answered'); }
}
