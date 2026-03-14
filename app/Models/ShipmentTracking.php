<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTracking extends Model
{
    protected $fillable = [
        'shipment_id', 'status', 'location', 'description',
        'latitude', 'longitude',
        'signatory_name', 'signature', 'delivery_photos',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'float',
            'longitude'       => 'float',
            'delivery_photos' => 'array',
            'occurred_at'     => 'datetime',
        ];
    }

    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function hasCoordinates(): bool { return $this->latitude && $this->longitude; }
}
