<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt',
        'meta_title', 'meta_description',
        'is_published', 'published_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function scopePublished($query) { return $query->where('is_published', true); }
    public function getRouteKeyName(): string { return 'slug'; }
}
