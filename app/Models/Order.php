<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'reference', 'status', 'currency',
        'subtotal', 'discount_amount', 'shipping_amount', 'tax_amount', 'total',
        'commission_amount', 'coupon_id', 'coupon_code', 'coupon_discount',
        'shipping_address_snapshot', 'billing_address_snapshot',
        'customer_notes', 'admin_notes', 'ip_address', 'user_agent',
        'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
        'cancellation_reason', 'refunded_amount',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'                  => 'float',
            'discount_amount'           => 'float',
            'shipping_amount'           => 'float',
            'tax_amount'                => 'float',
            'total'                     => 'float',
            'commission_amount'         => 'float',
            'coupon_discount'           => 'float',
            'refunded_amount'           => 'float',
            'shipping_address_snapshot' => 'array',
            'billing_address_snapshot'  => 'array',
            'paid_at'                   => 'datetime',
            'shipped_at'                => 'datetime',
            'delivered_at'              => 'datetime',
            'cancelled_at'              => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function statusHistories(): HasMany { return $this->hasMany(OrderStatusHistory::class)->latest(); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class, 'reference_id')->where('reference_type', Order::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class); }
    public function returns(): HasMany { return $this->hasMany(Return::class); }
    public function disputes(): HasMany { return $this->hasMany(Dispute::class); }

    public function isPaid(): bool { return $this->paid_at !== null; }
    public function isCancelled(): bool { return $this->status === 'cancelled'; }
    public function isDelivered(): bool { return $this->status === 'delivered'; }

    public function updateStatus(string $status, ?string $note = null, ?int $performedBy = null): void
    {
        $old = $this->status;
        $this->update(['status' => $status]);
        $this->statusHistories()->create([
            'from_status'  => $old,
            'to_status'    => $status,
            'note'         => $note,
            'performed_by' => $performedBy,
        ]);
    }

    public function scopePaid($query) { return $query->whereNotNull('paid_at'); }
    public function scopeStatus($query, string $status) { return $query->where('status', $status); }
}
