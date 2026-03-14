<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBankAccount extends Model
{
    protected $fillable = [
        'supplier_id', 'bank_name', 'account_holder_name',
        'account_number', 'iban', 'swift_bic', 'routing_number',
        'currency', 'is_default', 'is_verified',
    ];

    protected $hidden = ['account_number', 'iban'];

    protected function casts(): array
    {
        return [
            'is_default'  => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
