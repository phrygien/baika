<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        "parent_id",
        "name",
        "slug",
        "description",
        "image",
        "icon",
        "meta_title",
        "meta_description",
        "meta_keywords",
        "sort_order",
        "commission_rate",
        "is_active",
        "is_featured",
        "depth",
        "path",
    ];

    protected function casts(): array
    {
        return [
            "commission_rate" => "float",
            "is_active" => "boolean",
            "is_featured" => "boolean",
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, "parent_id");
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, "parent_id");
    }

    /** Tous les descendants récursivement */
    public function allChildren(): HasMany
    {
        return $this->children()->with("allChildren");
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, "category_attributes")
            ->withPivot("sort_order")
            ->withTimestamps()
            ->orderByPivot("sort_order");
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where("is_active", true);
    }

    public function scopeFeatured($query)
    {
        return $query->where("is_featured", true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull("parent_id");
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getAncestorIds(): array
    {
        return array_filter(explode("/", $this->path ?? ""));
    }

    public function getRouteKeyName(): string
    {
        return "slug";
    }
}
