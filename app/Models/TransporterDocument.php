<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterDocument extends Model
{
    protected $fillable = [
        'transporter_id', 'document_type', 'file_path', 'original_name',
        'expiry_date', 'status', 'notes', 'reviewed_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date'  => 'date',
            'reviewed_at'  => 'datetime',
        ];
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereDate('expiry_date', '<=', now()->addDays($days))
                     ->whereDate('expiry_date', '>=', now());
    }
}
