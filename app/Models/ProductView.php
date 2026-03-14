<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    protected $fillable = ['product_id', 'user_id', 'session_id', 'ip_address', 'viewed_at'];
    protected function casts(): array { return ['viewed_at' => 'datetime']; }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
