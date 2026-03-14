<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayout extends Model
{
    protected $fillable = [
        'supplier_id', 'bank_account_id', 'amount', 'currency',
        'status', 'reference', 'payment_method', 'notes',
        'paid_at', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'float',
            'paid_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SupplierBankAccount::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
