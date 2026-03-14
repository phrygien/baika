<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterVehicle extends Model
{
    protected $fillable = [
        'transporter_id', 'vehicle_type', 'brand', 'model',
        'plate_number', 'year', 'max_weight_kg', 'max_volume_cm3', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_weight_kg'  => 'float',
            'max_volume_cm3' => 'float',
            'is_active'      => 'boolean',
        ];
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
