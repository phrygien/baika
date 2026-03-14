<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodConfig extends Model
{
    protected $fillable = [
        'name', 'display_name', 'type', 'logo', 'config',
        'supported_countries', 'supported_currencies',
        'fee_percentage', 'fee_fixed',
        'min_amount', 'max_amount',
        'is_active', 'sort_order',
    ];

    protected $hidden = ['config'];

    protected function casts(): array
    {
        return [
            'config'               => 'encrypted:array',
            'supported_countries'  => 'array',
            'supported_currencies' => 'array',
            'fee_percentage'       => 'float',
            'fee_fixed'            => 'float',
            'min_amount'           => 'float',
            'max_amount'           => 'float',
            'is_active'            => 'boolean',
        ];
    }

    public function calculateFee(float $amount): float
    {
        return round($amount * ($this->fee_percentage / 100) + $this->fee_fixed, 2);
    }

    public function supportsCountry(string $code): bool
    {
        return empty($this->supported_countries) || in_array($code, $this->supported_countries);
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
