<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeEscalation extends Model
{
    protected $fillable = [
        'dispute_id', 'tier', 'reason', 'escalated_by', 'assigned_to',
        'escalated_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'escalated_at' => 'datetime',
            'resolved_at'  => 'datetime',
        ];
    }

    public function dispute(): BelongsTo { return $this->belongsTo(Dispute::class); }
    public function escalatedBy(): BelongsTo { return $this->belongsTo(User::class, 'escalated_by'); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
