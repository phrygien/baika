<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnRequest extends Model
{
    use SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'order_id', 'customer_id', 'supplier_id', 'return_policy_id',
        'return_number', 'status', 'resolution_type', 'reason', 'reason_detail',
        'requested_amount', 'approved_amount', 'refunded_amount',
        'photos', 'videos', 'customer_notes', 'admin_notes', 'supplier_notes',
        'reviewed_by', 'reviewed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'float',
            'approved_amount'  => 'float',
            'refunded_amount'  => 'float',
            'photos'           => 'array',
            'videos'           => 'array',
            'reviewed_at'      => 'datetime',
            'completed_at'     => 'datetime',
            'deleted_at'       => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function policy(): BelongsTo { return $this->belongsTo(ReturnPolicy::class, 'return_policy_id'); }
    public function items(): HasMany { return $this->hasMany(ReturnItem::class); }
    public function shipment(): HasOne { return $this->hasOne(ReturnShipment::class); }
    public function statusHistories(): HasMany { return $this->hasMany(ReturnStatusHistory::class)->latest(); }
    public function refunds(): HasMany { return $this->hasMany(ReturnRefund::class); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function updateStatus(string $status, ?string $note = null, string $performedBy = 'system', ?int $userId = null): void
    {
        $old = $this->status;
        $this->update(['status' => $status]);
        $this->statusHistories()->create([
            'from_status'   => $old,
            'to_status'     => $status,
            'note'          => $note,
            'performed_by'  => $performedBy,
            'performed_by_id' => $userId,
        ]);
    }

    public function scopeStatus($query, string $status) { return $query->where('status', $status); }
}
