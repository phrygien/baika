<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'message', 'type', 'target_audience',
        'is_dismissible', 'starts_at', 'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_audience' => 'array',
            'is_dismissible'  => 'boolean',
            'is_active'       => 'boolean',
            'starts_at'       => 'datetime',
            'ends_at'         => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                     ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
