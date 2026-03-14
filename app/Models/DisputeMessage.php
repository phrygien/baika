<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisputeMessage extends Model
{
    protected $fillable = [
        'dispute_id', 'sender_id', 'party',
        'message', 'attachments', 'is_internal',
        'read_by_customer_at', 'read_by_supplier_at', 'read_by_admin_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments'            => 'array',
            'is_internal'            => 'boolean',
            'read_by_customer_at'    => 'datetime',
            'read_by_supplier_at'    => 'datetime',
            'read_by_admin_at'       => 'datetime',
        ];
    }

    public function dispute(): BelongsTo { return $this->belongsTo(Dispute::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
}
