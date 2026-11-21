<?php

namespace Wsmallnews\Support\Filament\Widgets\Concerns;

use Livewire\Attributes\Locked;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteRegistry;

/**
 * Site declared by the rendering page, passed as a widget property.
 *
 * A widget is a Livewire component and a panel has no site context (SiteContext
 * is frontend-only), so the site must come from the page/resource that renders
 * it — never guessed from the record's scope. Locked keeps the client from
 * pointing the widget at another site.
 */
trait ResolvesSite
{
    #[Locked]
    public ?string $siteId = null;

    public function getSite(): ?Site
    {
        return $this->siteId === null ? null : app(SiteRegistry::class)->get($this->siteId);
    }
}
