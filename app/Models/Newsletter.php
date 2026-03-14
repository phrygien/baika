<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newsletter extends Model
{
    protected $fillable = [
        'email', 'user_id', 'first_name',
        'status', 'source', 'confirmation_token',
        'confirmed_at', 'unsubscribed_at', 'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at'    => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isActive(): bool { return $this->status === 'subscribed'; }
    public function scopeSubscribed($query) { return $query->where('status', 'subscribed'); }
}
