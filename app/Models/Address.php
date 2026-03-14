<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    protected $fillable = [
        'country_id', 'state_id', 'city_id',
        'type', 'label',
        'first_name', 'last_name', 'company_name',
        'address_line_1', 'address_line_2',
        'city_name', 'postal_code', 'phone',
        'latitude', 'longitude',
        'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude'   => 'float',
            'longitude'  => 'float',
            'is_default' => 'boolean',
            'is_active'  => 'boolean',
        ];
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function fullAddress(): string
    {
        return implode(', ', array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->city_name,
            $this->postal_code,
            $this->country?->name,
        ]));
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeShipping($query)
    {
        return $query->where('type', 'shipping');
    }
}
