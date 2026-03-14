<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlashSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description',
        'banner_image_desktop', 'banner_image_mobile', 'thumbnail_image',
        'background_color', 'text_color', 'badge_text',
        'type', 'scope', 'organizer_supplier_id',
        'teaser_starts_at', 'starts_at', 'ends_at', 'duration_minutes',
        'is_recurring', 'recurrence_pattern', 'recurrence_days', 'recurrence_ends_at',
        'eligible_user_tiers', 'requires_registration', 'max_orders_per_user',
        'status', 'is_featured', 'show_countdown', 'show_stock_level', 'show_sold_count',
        'total_products', 'total_views', 'total_orders', 'total_revenue', 'total_subscribers',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'recurrence_days'        => 'array',
            'eligible_user_tiers'    => 'array',
            'total_revenue'          => 'float',
            'is_recurring'           => 'boolean',
            'requires_registration'  => 'boolean',
            'is_featured'            => 'boolean',
            'show_countdown'         => 'boolean',
            'show_stock_level'       => 'boolean',
            'show_sold_count'        => 'boolean',
            'teaser_starts_at'       => 'datetime',
            'starts_at'              => 'datetime',
            'ends_at'                => 'datetime',
            'recurrence_ends_at'     => 'datetime',
            'approved_at'            => 'datetime',
        ];
    }

    public function organizerSupplier(): BelongsTo { return $this->belongsTo(Supplier::class, 'organizer_supplier_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function slots(): HasMany { return $this->hasMany(FlashSaleSlot::class)->orderBy('starts_at'); }
    public function products(): HasMany { return $this->hasMany(FlashSaleProduct::class); }
    public function analytics(): HasMany { return $this->hasMany(FlashSaleAnalytic::class); }
    public function supplierRequests(): HasMany { return $this->hasMany(FlashSaleSupplierRequest::class); }
    public function notifications(): HasMany { return $this->hasMany(FlashSaleNotification::class); }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isUpcoming(): bool { return $this->status === 'scheduled' && $this->starts_at->isFuture(); }
    public function isEnded(): bool { return in_array($this->status, ['ended', 'sold_out', 'cancelled']); }
    public function remainingMinutes(): int { return max(0, (int) now()->diffInMinutes($this->ends_at, false)); }

    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeFeatured($query) { return $query->where('is_featured', true); }
    public function scopeUpcoming($query) { return $query->where('status', 'scheduled')->where('starts_at', '>', now()); }
    public function getRouteKeyName(): string { return 'slug'; }
}
