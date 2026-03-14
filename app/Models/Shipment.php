<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'transporter_id', 'tracking_number', 'carrier_name',
        'status', 'shipping_method',
        'origin_address_snapshot', 'destination_address_snapshot',
        'weight_kg', 'length_cm', 'width_cm', 'height_cm',
        'shipping_cost', 'currency',
        'estimated_delivery_at', 'shipped_at', 'delivered_at',
        'delivery_notes', 'delivery_instructions',
    ];

    protected function casts(): array
    {
        return [
            'origin_address_snapshot'      => 'array',
            'destination_address_snapshot' => 'array',
            'weight_kg'                    => 'float',
            'shipping_cost'                => 'float',
            'estimated_delivery_at'        => 'datetime',
            'shipped_at'                   => 'datetime',
            'delivered_at'                 => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function transporter(): BelongsTo { return $this->belongsTo(Transporter::class); }
    public function items(): HasMany { return $this->hasMany(ShipmentItem::class); }
    public function trackings(): HasMany { return $this->hasMany(ShipmentTracking::class)->latest('occurred_at'); }
    public function latestTracking(): HasOne { return $this->hasOne(ShipmentTracking::class)->latestOfMany('occurred_at'); }
    public function rating(): HasOne { return $this->hasOne(ShipmentRating::class); }

    public function isDelivered(): bool { return $this->status === 'delivered'; }

    public function updateStatus(string $status, ?string $location = null, ?string $description = null, ?float $lat = null, ?float $lng = null): void
    {
        $this->update(['status' => $status]);
        $this->trackings()->create([
            'status'      => $status,
            'location'    => $location,
            'description' => $description,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'occurred_at' => now(),
        ]);
    }
}
