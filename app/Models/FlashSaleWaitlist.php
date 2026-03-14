<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleWaitlist extends Model
{
    protected $fillable = [
        'flash_sale_product_id', 'user_id', 'quantity_requested',
        'position', 'status',
        'notified_at', 'notification_expires_at', 'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'notified_at'              => 'datetime',
            'notification_expires_at'  => 'datetime',
            'purchased_at'             => 'datetime',
        ];
    }

    public function flashSaleProduct(): BelongsTo { return $this->belongsTo(FlashSaleProduct::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isExpired(): bool
    {
        return $this->notification_expires_at && $this->notification_expires_at->isPast();
    }
}
