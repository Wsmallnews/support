<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Wsmallnews\Support\Features\Site\SiteContext;

/**
 * Binds the page's site into the SiteContext for the whole Livewire request,
 * so the site-scoped settings repository resolves the right rows on both
 * mount (fill) and save.
 *
 * Requires CanBeConfigured on the using class (site-scoped settings pages are
 * registered per site via RegistersSites). The page reads its site via
 * CanBeConfigured::getSite(); the binding only feeds infrastructure that holds
 * no page instance — panel code still reads the site from getSite(), never
 * from SiteContext.
 */
trait InteractsWithSiteSettings
{
    public function bootInteractsWithSiteSettings(): void
    {
        $site = static::getSite();

        if ($site === null) {
            return;
        }

        app(SiteContext::class)->set($site, current_tenant());
    }
}
