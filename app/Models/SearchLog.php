<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'query', 'filters_applied',
        'results_count', 'clicked_product_id', 'led_to_purchase',
    ];

    protected function casts(): array
    {
        return [
            'filters_applied'  => 'array',
            'led_to_purchase'  => 'boolean',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function clickedProduct(): BelongsTo { return $this->belongsTo(Product::class, 'clicked_product_id'); }

    public function scopeNoResults($query) { return $query->where('results_count', 0); }
}
