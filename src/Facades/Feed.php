<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Feed\FeedRegistry;

/**
 * @method static static config(string $module, array $config, Site $site)
 * @method static mixed getConfig(string $module, Site $site, ?string $key = null, mixed $default = null)
 * @method static mixed resolveConfig(string $module, string $key, Site $site, mixed $default = null)
 * @method static static register(string $module, string $name, array $feed, Site $site)
 * @method static static registers(string $module, array $feeds, Site $site)
 * @method static void routes(string $module, Site $site)
 * @method static Collection<string, array<string, mixed>> moduleFeeds(string $module, Site $site)
 * @method static string feedUrl(string $name, Site $site)
 * @method static HtmlString autodiscoveryTags(string $module, Site $site)
 * @method static ?array getFeed(string $name, string $module, Site $site)
 * @method static HtmlString render(string $module, Site $site, ?string $name = null)
 * @method static static flush()
 *
 * @see FeedRegistry
 */
class Feed extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FeedRegistry::class;
    }
}
