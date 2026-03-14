<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    // ── Relations ──────────────────────────────────────────────────────────

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions'
        );
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains('name', $permission);
    }

    public function givePermission(string|int|Permission ...$permissions): void
    {
        $ids = array_map(fn ($p) => $p instanceof Permission
            ? $p->id
            : (is_int($p) ? $p : Permission::where('name', $p)->firstOrFail()->id),
            $permissions
        );
        $this->permissions()->syncWithoutDetaching($ids);
    }

    public function revokePermission(string|int|Permission ...$permissions): void
    {
        $ids = array_map(fn ($p) => $p instanceof Permission
            ? $p->id
            : (is_int($p) ? $p : Permission::where('name', $p)->firstOrFail()->id),
            $permissions
        );
        $this->permissions()->detach($ids);
    }

    public function syncPermissions(array $permissions): void
    {
        $ids = array_map(fn ($p) => $p instanceof Permission
            ? $p->id
            : (is_int($p) ? $p : Permission::where('name', $p)->firstOrFail()->id),
            $permissions
        );
        $this->permissions()->sync($ids);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
