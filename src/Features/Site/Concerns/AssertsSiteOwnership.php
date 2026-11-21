<?php

namespace Wsmallnews\Support\Features\Site\Concerns;

use InvalidArgumentException;
use Wsmallnews\Support\Features\Site\Site;

/**
 * One site belongs to exactly one module: registries that keep a per-site data
 * space (feed, sitemap, search, user config) only accept the owning module's id.
 *
 * Registration keeps the module argument so a plugin registering on a foreign
 * site fails loudly; reads derive it from the site through siteModule().
 */
trait AssertsSiteOwnership
{
    /** Module owning the site; the key of every per-site data space. */
    protected function siteModule(Site $site): string
    {
        return $site->pluginId;
    }

    protected function assertOwnership(string $module, Site $site): void
    {
        if ($module === $this->siteModule($site)) {
            return;
        }

        $service = str_replace('Registry', '', class_basename(static::class));

        throw new InvalidArgumentException(
            "{$service} module [{$module}] cannot register on site [{$site->id()}] owned by [{$site->pluginId}]."
        );
    }
}
