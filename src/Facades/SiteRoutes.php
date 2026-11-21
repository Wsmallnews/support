<?php

namespace Wsmallnews\Support\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteRouteRegistry;

/**
 * @method static SiteRouteRegistry extend(string $module, string $slot, Closure $callback)
 * @method static void apply(string $module, Site $site, string $slot)
 * @method static list<string> appliedSlots(string $module)
 * @method static SiteRouteRegistry flush(?string $module = null)
 *
 * @see SiteRouteRegistry
 */
class SiteRoutes extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SiteRouteRegistry::class;
    }
}
