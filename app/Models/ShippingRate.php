<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $fillable = [
        'transporter_id', 'origin_zone_id', 'destination_zone_id',
        'name', 'calculation_type', 'base_price',
        'price_per_kg', 'price_per_km', 'free_shipping_threshold',
        'estimated_days_min', 'estimated_days_max', 'max_weight_kg', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price'              => 'float',
            'price_per_kg'            => 'float',
            'price_per_km'            => 'float',
            'free_shipping_threshold' => 'float',
            'max_weight_kg'           => 'float',
            'is_active'               => 'boolean',
        ];
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function originZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'origin_zone_id');
    }

    public function destinationZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'destination_zone_id');
    }

    public function calculateCost(float $weightKg = 0, float $distanceKm = 0): float
    {
        return match ($this->calculation_type) {
            'fixed'   => $this->base_price,
            'per_kg'  => $this->base_price + ($this->price_per_kg * $weightKg),
            'per_km'  => $this->base_price + ($this->price_per_km * $distanceKm),
            'mixed'   => $this->base_price + ($this->price_per_kg * $weightKg) + ($this->price_per_km * $distanceKm),
            default   => $this->base_price,
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
