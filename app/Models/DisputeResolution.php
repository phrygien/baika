<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeResolution extends Model
{
    protected $fillable = [
        'dispute_id', 'resolved_by',
        'resolution_type', 'notes_to_customer', 'notes_to_supplier',
        'customer_refund_amount', 'supplier_deduction_amount', 'platform_absorption_amount',
        'currency', 'appeal_deadline', 'is_appealed',
    ];

    protected function casts(): array
    {
        return [
            'customer_refund_amount'     => 'float',
            'supplier_deduction_amount'  => 'float',
            'platform_absorption_amount' => 'float',
            'appeal_deadline'            => 'datetime',
            'is_appealed'                => 'boolean',
        ];
    }

    public function dispute(): BelongsTo { return $this->belongsTo(Dispute::class); }
    public function resolvedBy(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }
}
