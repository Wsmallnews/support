<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Container type of a panel-managed tree (navigation, category): the container
 * and its rows share the page's registration scope, and the container is
 * created lazily on the first visit.
 *
 * The scope pair and tenant are computed once and reused by the lookup and the
 * write path, so the two can never diverge.
 */
trait HasPanelScopedType
{
    protected ?array $panelScopedAttributes = null;

    /** Container model class of this tree. */
    abstract protected function typeModel(): string;

    /** Name / level / status of a newly created container. */
    abstract protected function typeDefaults(): array;

    /** Scope pair and tenant shared by the container and its rows. */
    protected function panelScopedAttributes(): array
    {
        return $this->panelScopedAttributes ??= [
            'team_id' => current_tenant()?->getKey(),
            'scope_type' => static::getScopeType(),
            'scope_id' => static::getScopeId(),
        ];
    }

    /** Container of the page's scope; created when the page is not in manage mode. */
    protected function resolvePanelType(): ?Model
    {
        $attributes = $this->panelScopedAttributes();

        $type = $this->typeModel()::query()
            ->panelContext([
                'scope_type' => $attributes['scope_type'],
                'scope_id' => $attributes['scope_id'],
            ])
            ->first();

        if ($type !== null || static::getCanManage()) {
            return $type;
        }

        return $this->typeModel()::query()->create([
            ...$attributes,
            ...$this->typeDefaults(),
        ]);
    }

    /** Container name from the scope suffix: sn-cms::footer → Footer. */
    protected function typeNameFromScope(): string
    {
        $scopeType = static::getScopeType();
        $label = str_contains($scopeType, '::') ? Str::after($scopeType, '::') : $scopeType;

        return Str::headline($label);
    }
}
