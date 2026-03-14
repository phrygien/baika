<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'reference', 'subject',
        'category', 'priority', 'status',
        'assigned_to', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at'   => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class)->oldest(); }

    public function scopeOpen($query) { return $query->whereNotIn('status', ['resolved', 'closed']); }
    public function scopeByPriority($query, string $p) { return $query->where('priority', $p); }
}
