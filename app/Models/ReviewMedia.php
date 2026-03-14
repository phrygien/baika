<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewMedia extends Model
{
    protected $fillable = [
        'review_id', 'type', 'file_path', 'thumbnail_path',
        'width', 'height', 'duration_seconds', 'file_size_kb',
        'sort_order', 'is_validated',
    ];

    protected function casts(): array
    {
        return ['is_validated' => 'boolean'];
    }

    public function review(): BelongsTo { return $this->belongsTo(Review::class); }
    public function isPhoto(): bool { return $this->type === 'photo'; }
    public function isVideo(): bool { return $this->type === 'video'; }
}
