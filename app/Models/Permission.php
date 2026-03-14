<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'display_name', 'group'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    // Scopes pratiques
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
