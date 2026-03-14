<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnItem extends Model
{
    protected $table = 'return_items';

    protected $fillable = [
        'return_id', 'order_item_id', 'quantity', 'reason',
        'condition_on_return', 'condition_on_receipt',
        'refund_amount', 'notes',
    ];

    protected function casts(): array
    {
        return ['refund_amount' => 'float'];
    }

    public function returnRequest(): BelongsTo { return $this->belongsTo(ReturnRequest::class, 'return_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
}
