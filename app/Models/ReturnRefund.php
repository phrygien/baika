<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRefund extends Model
{
    protected $fillable = [
        'return_id', 'transaction_id', 'amount', 'currency',
        'refund_method', 'status', 'notes', 'processed_at', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'float',
            'processed_at' => 'datetime',
        ];
    }

    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_id'); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function processedBy(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}
