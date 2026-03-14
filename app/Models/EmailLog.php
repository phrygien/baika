<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'user_id', 'to_email', 'from_email', 'subject', 'template',
        'status', 'provider_message_id', 'opened_at', 'clicked_at',
        'bounced_at', 'bounce_reason',
    ];

    protected function casts(): array
    {
        return [
            'opened_at'  => 'datetime',
            'clicked_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function scopeByTemplate($query, string $t) { return $query->where('template', $t); }
    public function scopeOpened($query) { return $query->whereNotNull('opened_at'); }
}
