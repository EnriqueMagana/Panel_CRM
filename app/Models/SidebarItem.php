<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SidebarItem extends Model
{
    protected $fillable = [
        'parent_id',
        'type',
        'label',
        'icon',
        'route_name',
        'route_fragment',
        'action_key',
        'permission_name',
        'active_route',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isPermittedTo(User $user, bool $checkParent = true): bool
    {
        if (filled($this->permission_name) && ! $user->can($this->permission_name)) {
            return false;
        }

        if (! $checkParent || $this->parent_id === null) {
            return true;
        }

        return $this->parent !== null && $this->parent->isPermittedTo($user, false);
    }

    public function isAccessibleTo(User $user): bool
    {
        if (! $this->is_active || ! $this->isPermittedTo($user)) {
            return false;
        }

        return $this->parent_id === null || $this->parent?->is_active === true;
    }
}
