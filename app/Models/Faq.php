<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question', 'answer', 'category', 'audience', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByAudience($query, string $audience) { return $query->where('audience', $audience); }
}
