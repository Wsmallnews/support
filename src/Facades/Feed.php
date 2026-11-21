<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Feed\FeedRegistry;

/**
 * Writes keep the module argument (ownership is asserted); reads take only the site,
 * whose plugin id is the module.
 *
 * @method static static config(string $module, array $config, Site $site)
 * @method static static registers(string $module, array $feeds, Site $site)
 * @method static static register(string $module, string $name, array $feed, Site $site)
 * @method static void routes(string $module, Site $site)
 * @method static mixed getConfig(Site $site, ?string $key = null, mixed $default = null)
 * @method static mixed resolveConfig(Site $site, string $key, mixed $default = null)
 * @method static Collection<string, array<string, mixed>> getFeeds(Site $site)
 * @method static ?array getFeed(string $name, Site $site)
 * @method static HtmlString autodiscoveryTags(Site $site)
 * @method static string feedUrl(string $name, Site $site)
 * @method static HtmlString render(Site $site, ?string $name = null)
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
