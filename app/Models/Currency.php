<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'exchange_rate',
        'is_default', 'is_active', 'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'exchange_rate'   => 'float',
            'is_default'      => 'boolean',
            'is_active'       => 'boolean',
            'last_updated_at' => 'datetime',
        ];
    }

    public function convert(float $amount, string $toCurrencyCode): float
    {
        $target = static::where('code', $toCurrencyCode)->firstOrFail();
        return round($amount / $this->exchange_rate * $target->exchange_rate, 2);
    }

    public static function default(): self
    {
        return static::where('is_default', true)->firstOrFail();
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
