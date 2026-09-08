<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Feed\FeedRegistry;

/**
 * @method static static config(string $module, array $config)
 * @method static static register(string $module, string $name, array $feed)
 * @method static static forget(string $name)
 * @method static void routes(string $module)
 * @method static Collection<string, array<string, mixed>> available()
 * @method static Collection<string, array<string, mixed>> moduleFeeds(string $module)
 * @method static ?array getFeed(string $name, ?string $module = null)
 * @method static HtmlString render(?string $name = null)
 * @method static HtmlString renderModule(string $module, ?string $name = null)
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
