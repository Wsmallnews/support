<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;

/**
 * @method static static config(string $module, array $config, Site $site)
 * @method static static register(string $module, array $source, Site $site)
 * @method static static registers(string $module, array $sources, Site $site)
 * @method static static robots(string $module, array $rules)
 * @method static void routes(string $module, Site $site)
 * @method static Collection<int, array<string, mixed>> getSources(string $module, Site $site)
 * @method static mixed getConfig(string $module, Site $site, ?string $key = null, mixed $default = null)
 * @method static mixed resolveConfig(string $module, string $key, Site $site, mixed $default = null)
 * @method static HtmlString render(string $module, Site $site)
 * @method static string renderRobots()
 * @method static static flush(string $module, Site $site)
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
