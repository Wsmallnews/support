<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Exceptions\InvalidScopeException;

/**
 * Resolves the container type of a scoped tree (category, navigation) and
 * guarantees that the container and its rows share one data space.
 *
 * The host component owns the data space through typeScopeable(); the default
 * is the site base scope, so a component without a partition needs no wiring.
 * The type is resolved at most once per request and never enters the Livewire
 * snapshot, so a widget update does not carry a whole model back and forth.
 */
trait HasScopedType
{
    protected ?Model $resolvedType = null;

    /** Container model class of this tree. */
    abstract protected function typeModel(): string;

    /** Scope shared by the container type and its rows. */
    abstract protected function typeScopeable(): array;

    /** Container id the caller pinned, if any. */
    abstract protected function typeIdValue(): ?int;

    /** Whether the container exists in this scope. */
    public function hasType(): bool
    {
        return $this->resolveType() !== null;
    }

    /** Resolve the container inside the same scope as its rows. */
    protected function resolveType(): ?Model
    {
        if ($this->resolvedType !== null) {
            return $this->resolvedType;
        }

        $typeId = $this->typeIdValue();

        return $this->resolvedType = $this->typeModel()::query()
            ->forScopeable($this->typeScopeable())
            ->when($typeId, fn ($query) => $query->whereKey($typeId))
            ->first();
    }

    /**
     * Scope and container id of this tree, resolved once and consumed by both
     * the container lookup and the row query.
     *
     * @return array{scope_type: string, scope_id: int, type_id: int|string|null, team_id: int|string|null}
     */
    protected function scopedType(): array
    {
        $scopeable = $this->typeScopeable();
        $type = $this->resolveType();

        if ($type !== null && (
            $type->getAttribute('scope_type') !== $scopeable['scope_type']
            || (int) $type->getAttribute('scope_id') !== (int) $scopeable['scope_id']
        )) {
            throw InvalidScopeException::typeScopeMismatch(
                (string) $type->getKey(),
                (string) $type->getAttribute('scope_type'),
                $scopeable['scope_type'],
            );
        }

        return [
            ...$scopeable,
            'type_id' => $type?->getKey(),
            'team_id' => current_tenant()?->getKey(),
        ];
    }
}
