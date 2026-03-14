<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentRating extends Model
{
    protected $fillable = [
        'shipment_id', 'customer_id',
        'overall_rating', 'speed_rating', 'care_rating', 'communication_rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating'       => 'float',
            'speed_rating'         => 'float',
            'care_rating'          => 'float',
            'communication_rating' => 'float',
        ];
    }

    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
}
