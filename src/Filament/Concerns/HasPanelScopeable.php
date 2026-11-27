<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Livewire\Attributes\Locked;
use Wsmallnews\Support\Exceptions\InvalidScopeException;

/**
 * Data space of a panel-embedded component (page, widget, nested component).
 *
 * A panel has no SiteContext, so the rendering page/resource must pass the pair
 * down as a `scopeable` prop — the same shape `CanBeConfigured::getScopeable()`
 * returns. Without it the component fails closed instead of guessing a scope
 * from the record or a module default.
 */
trait HasPanelScopeable
{
    /** @var array{scope_type: string, scope_id: int}|null */
    #[Locked]
    public ?array $scopeable = null;

    /**
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException
     */
    protected function panelScopeable(): array
    {
        return $this->scopeable ?? throw InvalidScopeException::missingSiteBinding(static::class);
    }
}
