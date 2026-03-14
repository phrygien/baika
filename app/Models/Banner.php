<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'image_desktop', 'image_mobile', 'link_url',
        'type', 'position', 'sort_order',
        'background_color', 'text_color',
        'target_audience', 'starts_at', 'ends_at',
        'is_active', 'impression_count', 'click_count',
    ];

    protected function casts(): array
    {
        return [
            'target_audience' => 'array',
            'is_active'       => 'boolean',
            'starts_at'       => 'datetime',
            'ends_at'         => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->lte(now()))
            && (! $this->ends_at || $this->ends_at->gte(now()));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                     ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
