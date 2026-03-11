<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;

/**
 * Scopeable trait for Livewire components.
 * Provides locked scope properties to prevent tampering.
 */
trait Scopeable
{
    #[Locked]
    public string $scopeType = 'default';

    #[Locked]
    public int $scopeId = 0;

    /**
     * Get the scopeable array for database queries.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function getScopeable(): array
    {
        return ['scope_type' => $this->scopeType, 'scope_id' => $this->scopeId];
    }

    /**
     * Get the scope type.
     */
    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    /**
     * Get the scope ID.
     */
    public function getScopeId(): int
    {
        return $this->scopeId;
    }

    /**
     * Get a scoped query builder for the given model.
     * Convenience method to quickly get a scoped query.
     *
     * @param  string  $modelClass
     * @return Builder
     */
    // protected function getScopedQuery(string $modelClass = null)
    // {
    //     if (! $modelClass) {
    //         throw new \InvalidArgumentException('Model class is required for getScopedQuery()');
    //     }

    //     return $modelClass::scopeable($this->scopeType, $this->scopeId);
    // }
}
