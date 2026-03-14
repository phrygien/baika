<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'sort_order',
        'is_filterable', 'is_visible', 'is_variation', 'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_filterable' => 'boolean',
            'is_visible'    => 'boolean',
            'is_variation'  => 'boolean',
            'is_required'   => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attributes')
                    ->withPivot('sort_order')
                    ->withTimestamps();
    }

    public function scopeFilterable($query)
    {
        return $query->where('is_filterable', true);
    }

    public function scopeVariation($query)
    {
        return $query->where('is_variation', true);
    }
}
