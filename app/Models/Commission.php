<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    protected $fillable = [
        'order_item_id', 'supplier_id', 'payout_id',
        'rate', 'gross_amount', 'commission_amount', 'net_amount',
        'currency', 'status',
    ];

    protected function casts(): array
    {
        return [
            'rate'              => 'float',
            'gross_amount'      => 'float',
            'commission_amount' => 'float',
            'net_amount'        => 'float',
        ];
    }

    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function payout(): BelongsTo { return $this->belongsTo(SupplierPayout::class); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeBySupplier($query, int $id) { return $query->where('supplier_id', $id); }
}
