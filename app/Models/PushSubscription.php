<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'platform', 'endpoint', 'p256dh_key', 'auth_token',
        'device_name', 'is_active', 'last_used_at',
    ];

    protected $hidden = ['p256dh_key', 'auth_token'];

    protected function casts(): array
    {
        return [
            'is_active'    => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
