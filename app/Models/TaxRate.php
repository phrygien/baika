<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    protected $fillable = [
        'name', 'country_id', 'state_id', 'category_id', 'rate', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate'      => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function state(): BelongsTo { return $this->belongsTo(State::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
