<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReminder extends Model
{
    protected $fillable = [
        'order_item_id', 'user_id', 'channel', 'status',
        'attempt_number', 'send_at', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
