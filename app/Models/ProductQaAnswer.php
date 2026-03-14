<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductQaAnswer extends Model
{
    protected $table = 'product_qa_answers';

    protected $fillable = [
        'question_id', 'user_id', 'answer_type', 'answer',
        'is_accepted', 'status', 'helpful_count', 'not_helpful_count',
    ];

    protected function casts(): array
    {
        return ['is_accepted' => 'boolean'];
    }

    public function question(): BelongsTo { return $this->belongsTo(ProductQa::class, 'question_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function votes(): HasMany { return $this->hasMany(ProductQaVote::class, 'answer_id'); }
}
