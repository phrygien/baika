<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewSummaryStat extends Model
{
    protected $table = 'review_summary_stats';

    protected $fillable = [
        'product_id', 'total_reviews', 'average_rating',
        'rating_distribution', 'average_quality_rating',
        'average_value_rating', 'average_accuracy_rating', 'average_packaging_rating',
        'reviews_with_photos', 'reviews_with_videos',
        'size_feedback_summary', 'top_pros', 'top_cons',
        'recommendation_percentage', 'last_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'average_rating'           => 'float',
            'average_quality_rating'   => 'float',
            'average_value_rating'     => 'float',
            'average_accuracy_rating'  => 'float',
            'average_packaging_rating' => 'float',
            'recommendation_percentage'=> 'float',
            'rating_distribution'      => 'array',
            'size_feedback_summary'    => 'array',
            'top_pros'                 => 'array',
            'top_cons'                 => 'array',
            'last_calculated_at'       => 'datetime',
        ];
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
