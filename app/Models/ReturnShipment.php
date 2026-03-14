<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnShipment extends Model
{
    protected $fillable = [
        'return_id', 'tracking_number', 'carrier_name',
        'return_label_path', 'return_label_expires_at',
        'status', 'shipping_cost', 'who_paid_shipping',
        'shipped_at', 'received_at',
    ];

    protected function casts(): array
    {
        return [
            'return_label_expires_at' => 'datetime',
            'shipping_cost'           => 'float',
            'shipped_at'              => 'datetime',
            'received_at'             => 'datetime',
        ];
    }

    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_id'); }
}
