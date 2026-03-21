<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

use Laravel\Scout\Searchable;

class Product extends Model
{
    use SoftDeletes, Searchable;

    protected $fillable = [
        "supplier_id",
        "category_id",
        "brand_id",
        "name",
        "slug",
        "sku",
        "short_description",
        "description",
        "base_price",
        "compare_at_price",
        "cost_price",
        "currency",
        "weight_kg",
        "length_cm",
        "width_cm",
        "height_cm",
        "requires_shipping",
        "is_digital",
        "digital_file",
        "status",
        "rejection_reason",
        "is_featured",
        "is_active",
        "track_inventory",
        "low_stock_threshold",
        "origin_country",
        "hs_code",
        "barcode",
        "meta_title",
        "meta_description",
        "meta_keywords",
        "average_rating",
        "total_reviews",
        "total_sold",
        "total_views",
        "published_at",
        "approved_at",
        "approved_by",
    ];

    protected function casts(): array
    {
        return [
            "base_price" => "float",
            "compare_at_price" => "float",
            "cost_price" => "float",
            "weight_kg" => "float",
            "length_cm" => "float",
            "width_cm" => "float",
            "height_cm" => "float",
            "average_rating" => "float",
            "requires_shipping" => "boolean",
            "is_digital" => "boolean",
            "is_featured" => "boolean",
            "is_active" => "boolean",
            "track_inventory" => "boolean",
            "published_at" => "datetime",
            "approved_at" => "datetime",
            "deleted_at" => "datetime",
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "approved_by");
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy("sort_order");
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where("is_primary", true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, "product_tag");
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy("sort_order");
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where("is_active", true);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class)->whereNull("product_variant_id");
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where("status", "approved");
    }

    public function reviewStats(): HasOne
    {
        return $this->hasOne(ReviewSummaryStat::class);
    }

    public function qa(): HasMany
    {
        return $this->hasMany(ProductQa::class)->where("status", "answered");
    }

    public function views(): HasMany
    {
        return $this->hasMany(ProductView::class);
    }

    public function flashSaleProducts(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where("status", "approved")->where("is_active", true);
    }

    public function scopeFeatured($query)
    {
        return $query->where("is_featured", true);
    }

    public function scopeInStock($query)
    {
        return $query->whereHas(
            "inventory",
            fn($q) => $q->where("quantity_in_stock", ">", 0),
        );
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where("supplier_id", $supplierId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isOnSale(): bool
    {
        return $this->compare_at_price &&
            $this->compare_at_price > $this->base_price;
    }

    public function discountPercentage(): int
    {
        if (!$this->isOnSale()) {
            return 0;
        }
        return (int) round(
            (1 - $this->base_price / $this->compare_at_price) * 100,
        );
    }

    public function effectivePrice(): float
    {
        return $this->base_price;
    }

    public function getRouteKeyName(): string
    {
        return "slug";
    }

    // ── Scout ──────────────────────────────────────────────────────────────

    public function toSearchableArray(): array
    {
        return [
            "id" => (string) $this->id,
            "slug" => $this->slug,
            "sku" => $this->sku,
            "name" => $this->name,
            "short_description" => $this->short_description,
            "description" => strip_tags($this->description ?? ""),
            "barcode" => $this->barcode,

            // Relations dénormalisées
            "supplier_id" => $this->supplier_id,
            "supplier_name" => $this->supplier?->shop_name,
            "category_id" => $this->category_id,
            "category_name" => $this->category?->name,
            "brand_id" => $this->brand_id,
            "brand_name" => $this->brand?->name,

            // Pricing
            "base_price" => (float) $this->base_price,
            "compare_at_price" => $this->compare_at_price
                ? (float) $this->compare_at_price
                : null,
            "currency" => $this->currency ?? "USD",

            // Flags
            "status" => $this->status,
            "is_active" => (bool) $this->is_active,
            "is_featured" => (bool) $this->is_featured,
            "is_digital" => (bool) $this->is_digital,

            // Stats
            "average_rating" => $this->average_rating
                ? (float) $this->average_rating
                : null,
            "total_reviews" => (int) ($this->total_reviews ?? 0),
            "total_sold" => (int) ($this->total_sold ?? 0),
            "total_views" => (int) ($this->total_views ?? 0),

            // Image
            "image_path" => $this->primaryImage?->image_path,

            // Timestamps
            "created_at" => $this->created_at?->timestamp,
            "published_at" => $this->published_at?->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        // Indexer uniquement les produits non supprimés
        return $this->deleted_at === null;
    }
}
