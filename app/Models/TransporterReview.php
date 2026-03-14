<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterReview extends Model
{
    protected $fillable = [
        'transporter_id', 'customer_id', 'shipment_id',
        'overall_rating', 'speed_rating', 'care_rating',
        'communication_rating', 'professionalism_rating', 'accuracy_rating',
        'comment', 'status',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating'         => 'float',
            'speed_rating'           => 'float',
            'care_rating'            => 'float',
            'communication_rating'   => 'float',
            'professionalism_rating' => 'float',
            'accuracy_rating'        => 'float',
        ];
    }

    public function transporter(): BelongsTo { return $this->belongsTo(Transporter::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
}
