<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name', 'country_id', 'state_id', 'city_id', 'postal_codes',
        'latitude_center', 'longitude_center', 'radius_km',
        'polygon_coordinates', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude_center'     => 'float',
            'longitude_center'    => 'float',
            'radius_km'           => 'float',
            'polygon_coordinates' => 'array',
            'is_active'           => 'boolean',
        ];
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

    public function transporters(): BelongsToMany
    {
        return $this->belongsToMany(Transporter::class, 'transporter_zones')
                    ->withPivot('is_pickup_available', 'is_delivery_available')
                    ->withTimestamps();
    }

    public function originRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'origin_zone_id');
    }

    public function destinationRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'destination_zone_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
