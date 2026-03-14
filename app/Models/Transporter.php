<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transporter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'country_id', 'company_name', 'slug', 'logo',
        'description', 'type', 'registration_number', 'tax_number',
        'website', 'status', 'rejection_reason', 'commission_rate',
        'max_weight_kg', 'max_volume_cm3', 'handles_fragile',
        'handles_cold_chain', 'is_featured', 'is_verified',
        'average_rating', 'total_reviews', 'total_deliveries',
        'approved_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate'    => 'float',
            'average_rating'     => 'float',
            'handles_fragile'    => 'boolean',
            'handles_cold_chain' => 'boolean',
            'is_featured'        => 'boolean',
            'is_verified'        => 'boolean',
            'approved_at'        => 'datetime',
            'deleted_at'         => 'datetime',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TransporterDocument::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(TransporterVehicle::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(TransporterBankAccount::class);
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class, 'transporter_zones')
                    ->withPivot('is_pickup_available', 'is_delivery_available')
                    ->withTimestamps();
    }

    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TransporterReview::class);
    }

    public function wallet(): MorphMany
    {
        return $this->morphMany(Wallet::class, 'owner');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeLocal($query)
    {
        return $query->where('type', 'local');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
