<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'reference_type', 'reference_id',
        'payment_method_id', 'type', 'status',
        'amount', 'fee_amount', 'net_amount', 'currency',
        'gateway_reference', 'gateway_response', 'metadata',
        'failed_at', 'failure_reason', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'float',
            'fee_amount'        => 'float',
            'net_amount'        => 'float',
            'gateway_response'  => 'array',
            'metadata'          => 'array',
            'failed_at'         => 'datetime',
            'paid_at'           => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function paymentMethod(): BelongsTo { return $this->belongsTo(PaymentMethodConfig::class, 'payment_method_id'); }
    public function reference(): MorphTo { return $this->morphTo('reference'); }

    public function isPaid(): bool { return $this->status === 'completed' && $this->paid_at !== null; }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeByType($query, string $type) { return $query->where('type', $type); }
}
