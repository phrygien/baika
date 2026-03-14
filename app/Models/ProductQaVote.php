<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQaVote extends Model
{
    protected $table = 'product_qa_votes';
    protected $fillable = ['answer_id', 'user_id', 'is_helpful'];
    protected function casts(): array { return ['is_helpful' => 'boolean']; }

    public function answer(): BelongsTo { return $this->belongsTo(ProductQaAnswer::class, 'answer_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
