<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleCartReservation extends Model
{
    protected $fillable = [
        'flash_sale_product_id', 'user_id', 'session_id',
        'quantity', 'cart_item_token', 'status',
        'expires_at', 'converted_at', 'released_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'    => 'datetime',
            'converted_at'  => 'datetime',
            'released_at'   => 'datetime',
        ];
    }

    public function flashSaleProduct(): BelongsTo { return $this->belongsTo(FlashSaleProduct::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isExpired(): bool { return $this->expires_at->isPast() && $this->status === 'active'; }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeExpired($query) { return $query->where('status', 'active')->where('expires_at', '<', now()); }
}
