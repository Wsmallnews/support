<?php

namespace Wsmallnews\Support\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;

/**
 * Resolve the current frontend site from the matched route name and write it
 * into the request-scoped SiteContext.
 *
 * Routes are named "{pluginId}.{panelId}.{siteKey}.{name}" (see SiteConfig), so
 * the registry can match by the longest registered prefix without passing a site
 * id through middleware parameters.
 */
class ResolveSite
{
    public function __construct(protected SiteRegistry $registry) {}

    public function handle($request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        $site = $routeName ? $this->registry->matchByRouteNamePrefix($routeName) : null;

        if ($site instanceof Site) {
            app(SiteContext::class)->set(
                $site,
                $site->mode->isTenant() ? current_tenant() : null,
                $this->registry->scopeableFor($site),
            );
        }

        return $next($request);
    }
}
