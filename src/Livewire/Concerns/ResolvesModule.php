<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;
use RuntimeException;

/**
 * Module id of the component, defaulting to the site's owner.
 *
 * Composition is keyed by module (an asset of the registering plugin); search,
 * feed and sitemap are keyed by site and derive the module themselves, so they
 * never read this value.
 *
 * Requires ScopedBySite (every Support Livewire component has it).
 */
trait ResolvesModule
{
    #[Locked]
    public ?string $module = null;

    public function getModuleId(): string
    {
        return $this->module ?? $this->site()?->pluginId
            ?? throw new RuntimeException(sprintf(
                'Component [%s] needs a site context or an explicit module.',
                static::class
            ));
    }
}
