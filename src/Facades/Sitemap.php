<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;

/**
 * Writes keep the module argument (ownership is asserted); reads take only the site,
 * whose plugin id is the module.
 *
 * @method static static config(string $module, array $config, Site $site)
 * @method static static register(string $module, array $source, Site $site)
 * @method static static registers(string $module, array $sources, Site $site)
 * @method static static robots(string $module, array $rules)
 * @method static void routes(string $module, Site $site)
 * @method static Collection<int, array<string, mixed>> getSources(Site $site)
 * @method static mixed getConfig(Site $site, ?string $key = null, mixed $default = null)
 * @method static mixed resolveConfig(Site $site, string $key, mixed $default = null)
 * @method static HtmlString render(Site $site)
 * @method static string renderRobots()
 * @method static static flush(Site $site)
 *
 * @see SitemapRegistry
 */
class Sitemap extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SitemapRegistry::class;
    }
}
