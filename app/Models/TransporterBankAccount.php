<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransporterBankAccount extends Model
{
    protected $fillable = [
        'transporter_id', 'bank_name', 'account_holder_name',
        'account_number', 'iban', 'swift_bic', 'currency',
        'is_default', 'is_verified',
    ];

    protected $hidden = ['account_number', 'iban'];

    protected function casts(): array
    {
        return [
            'is_default'  => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }
}
