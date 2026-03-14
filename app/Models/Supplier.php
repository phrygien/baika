<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'country_id', 'shop_name', 'slug', 'logo', 'banner',
        'description', 'business_type', 'registration_number', 'tax_number',
        'website', 'status', 'rejection_reason', 'commission_rate',
        'is_featured', 'is_verified', 'average_rating',
        'total_reviews', 'total_sales', 'total_products',
        'approved_at', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate'  => 'float',
            'average_rating'   => 'float',
            'is_featured'      => 'boolean',
            'is_verified'      => 'boolean',
            'approved_at'      => 'datetime',
            'deleted_at'       => 'datetime',
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
        return $this->hasMany(SupplierDocument::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SupplierBankAccount::class);
    }

    public function defaultBankAccount(): HasMany
    {
        return $this->bankAccounts()->where('is_default', true);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(SupplierPayout::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('status', 'approved')->where('is_active', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SupplierReview::class);
    }

    public function wallet(): MorphMany
    {
        return $this->morphMany(Wallet::class, 'owner');
    }

    public function flashSaleRequests(): HasMany
    {
        return $this->hasMany(FlashSaleSupplierRequest::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
