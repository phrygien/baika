<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnPolicy extends Model
{
    protected $table = 'return_policies';

    protected $fillable = [
        'supplier_id', 'return_window_days', 'who_pays_return_shipping',
        'accepted_reasons', 'requires_original_packaging',
        'requires_all_tags', 'restocking_fee_percentage',
        'refund_methods', 'additional_conditions', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'accepted_reasons'             => 'array',
            'refund_methods'               => 'array',
            'requires_original_packaging'  => 'boolean',
            'requires_all_tags'            => 'boolean',
            'restocking_fee_percentage'    => 'float',
            'is_active'                    => 'boolean',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }

    public function isGlobal(): bool { return is_null($this->supplier_id); }
}
