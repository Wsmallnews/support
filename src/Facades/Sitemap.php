<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;

/**
 * @method static static config(string $module, array $config)
 * @method static static register(string $module, array $source)
 * @method static static registers(string $module, array $sources)
 * @method static static robots(string $module, array $rules)
 * @method static static forget(string $module)
 * @method static Collection<int, array<string, mixed>> getSources(string $module)
 * @method static array<int, string> getModules()
 * @method static HtmlString render()
 * @method static string renderRobots()
 * @method static static flush()
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
