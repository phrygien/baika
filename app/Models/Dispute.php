<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dispute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id', 'order_item_id', 'return_id',
        'complainant_id', 'defendant_id', 'defendant_type',
        'dispute_number', 'type', 'status', 'priority',
        'subject', 'description', 'photos', 'videos',
        'requested_resolution', 'requested_amount',
        'seller_response_deadline', 'auto_resolve_at',
        'assigned_to', 'resolved_at', 'resolution_summary',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount'         => 'float',
            'photos'                   => 'array',
            'videos'                   => 'array',
            'seller_response_deadline' => 'datetime',
            'auto_resolve_at'          => 'datetime',
            'resolved_at'              => 'datetime',
            'deleted_at'               => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_id'); }
    public function complainant(): BelongsTo { return $this->belongsTo(User::class, 'complainant_id'); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function evidences(): HasMany { return $this->hasMany(DisputeEvidence::class); }
    public function messages(): HasMany { return $this->hasMany(DisputeMessage::class)->latest(); }
    public function statusHistories(): HasMany { return $this->hasMany(DisputeStatusHistory::class)->latest(); }
    public function escalations(): HasMany { return $this->hasMany(DisputeEscalation::class); }
    public function resolution(): HasOne { return $this->hasOne(DisputeResolution::class); }

    public function isOpen(): bool { return ! in_array($this->status, ['resolved', 'closed', 'cancelled']); }

    public function updateStatus(string $status, ?string $note = null, ?int $userId = null): void
    {
        $old = $this->status;
        $this->update(['status' => $status]);
        $this->statusHistories()->create([
            'from_status'  => $old,
            'to_status'    => $status,
            'note'         => $note,
            'performed_by' => $userId,
        ]);
    }

    public function scopeOpen($query) { return $query->whereNotIn('status', ['resolved', 'closed', 'cancelled']); }
    public function scopePriority($query, string $p) { return $query->where('priority', $p); }
}
