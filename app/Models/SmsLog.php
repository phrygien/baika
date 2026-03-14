<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'user_id', 'phone', 'message', 'type',
        'status', 'provider', 'provider_message_id', 'cost', 'currency',
        'sent_at', 'failed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'cost'       => 'float',
            'sent_at'    => 'datetime',
            'failed_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
