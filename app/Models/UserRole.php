<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model UserRole
 *
 * Table dédiée multi-rôles avec historique complet.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $role_id
 * @property int|null    $assigned_by
 * @property int|null    $revoked_by
 * @property string      $assigned_at
 * @property string|null $expires_at
 * @property string|null $revoked_at
 * @property string      $status       active|expired|revoked|suspended
 * @property bool        $is_primary
 * @property string|null $notes
 * @property array|null  $metadata
 */
class UserRole extends Model
{
    protected $fillable = [
        'user_id', 'role_id',
        'assigned_by', 'revoked_by',
        'assigned_at', 'expires_at', 'revoked_at',
        'status', 'is_primary',
        'notes', 'metadata',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'expires_at'  => 'datetime',
        'revoked_at'  => 'datetime',
        'is_primary'  => 'boolean',
        'metadata'    => 'array',
    ];

    // =========================================================================
    //  RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function revokedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // =========================================================================
    //  SCOPES
    // =========================================================================

    /** Rôles actuellement actifs et non expirés */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where(fn (Builder $q) =>
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now())
                     );
    }

    /** Rôles expirés */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) =>
            $q->where('status', 'expired')
              ->orWhere(fn (Builder $q2) =>
                  $q2->where('status', 'active')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now())
              )
        );
    }

    /** Rôles révoqués */
    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', 'revoked');
    }

    /** Rôle principal */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /** Filtrer par user */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // =========================================================================
    //  ÉTATS
    // =========================================================================

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // =========================================================================
    //  ACTIONS
    // =========================================================================

    /**
     * Révoquer ce rôle (soft — conserve l'historique).
     */
    public function revoke(?int $revokedBy = null): void
    {
        $this->update([
            'status'     => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
            'is_primary' => false,
        ]);
    }

    /**
     * Suspendre temporairement ce rôle.
     */
    public function suspend(?string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'notes'  => $reason ?? $this->notes,
        ]);
    }

    /**
     * Réactiver un rôle suspendu.
     */
    public function restore(): void
    {
        $this->update([
            'status'     => 'active',
            'revoked_at' => null,
            'revoked_by' => null,
        ]);
    }

    /**
     * Marquer comme expiré (appelé par le job de nettoyage).
     */
    public function markExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    // =========================================================================
    //  BOOT — auto-expiration
    // =========================================================================

    protected static function booted(): void
    {
        // Avant chaque lecture, si expires_at est dépassé → mettre à jour le statut
        static::retrieved(function (UserRole $userRole) {
            if ($userRole->status === 'active'
                && $userRole->expires_at !== null
                && $userRole->expires_at->isPast()
            ) {
                $userRole->markExpired();
            }
        });
    }
}
