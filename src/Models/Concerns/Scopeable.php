<?php

namespace Wsmallnews\Support\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * Scopeable trait for Eloquent models.
 * Provides query scopes for filtering by scope_type and scope_id.
 */
trait Scopeable
{
    /**
     * Get the scopeable array from current record
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function getScopeable(): array
    {
        return ['scope_type' => $this->scope_type, 'scope_id' => $this->scope_id];
    }

    /**
     * Get the scope type.
     */
    public function getScopeType(): string
    {
        return $this->scope_type;
    }

    /**
     * Get the scope ID.
     */
    public function getScopeId(): int
    {
        return $this->scope_id;
    }

    /**
     * Scope query by scope type.
     */
    public function scopeScopeType(Builder $query, string $scope_type): Builder
    {
        return $query->where('scope_type', $scope_type);
    }

    /**
     * Scope query by scope ID(s).
     */
    public function scopeScopeId(Builder $query, int | array $scope_id = 0): Builder
    {
        $scope_id = Arr::wrap($scope_id);

        return $query->whereIn('scope_id', $scope_id);
    }

    /**
     * Scope query by both scope type and scope ID(s).
     */
    public function scopeScopeable(Builder $query, string $scope_type, int | array $scope_id = 0): Builder
    {
        return $query->scopeType($scope_type)->scopeId($scope_id);
    }

    /**
     * Initialize scope attributes when creating a new model instance.
     */
    // protected function initializeDefaultScopeAttributes(): void
    // {
    //     if (! isset($this->attributes['scope_type'])) {
    //         $this->attributes['scope_type'] = null;
    //     }

    //     if (! isset($this->attributes['scope_id'])) {
    //         $this->attributes['scope_id'] = 0;
    //     }
    // }
}
