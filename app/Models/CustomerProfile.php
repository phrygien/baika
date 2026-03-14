<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id', 'total_spent', 'orders_count',
        'cancelled_orders_count', 'returned_orders_count',
        'loyalty_points', 'loyalty_tier',
        'notification_preferences', 'favorite_categories',
    ];

    protected function casts(): array
    {
        return [
            'total_spent'               => 'float',
            'notification_preferences'  => 'array',
            'favorite_categories'       => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function loyaltyTransactions(): HasMany { return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id'); }

    public function addPoints(int $points, string $type, ?int $orderId = null): void
    {
        $before = $this->loyalty_points;
        $this->increment('loyalty_points', $points);
        $this->loyaltyTransactions()->create([
            'type'           => $type,
            'points'         => $points,
            'points_before'  => $before,
            'points_after'   => $before + $points,
            'order_id'       => $orderId,
        ]);
        $this->recalculateTier();
    }

    public function recalculateTier(): void
    {
        $tier = match(true) {
            $this->loyalty_points >= 10000 => 'platinum',
            $this->loyalty_points >= 5000  => 'gold',
            $this->loyalty_points >= 1000  => 'silver',
            default                        => 'bronze',
        };
        $this->update(['loyalty_tier' => $tier]);
    }
}
