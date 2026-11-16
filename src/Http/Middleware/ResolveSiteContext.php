<?php

namespace Wsmallnews\Support\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Wsmallnews\Support\Exceptions\InvalidSiteException;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Resolve the site context of a frontend request in one pass:
 *
 * 1. the site — by route-name matching ({pluginId}.{panelId}.{siteKey}.{name}),
 *    or by an explicit plugin parameter: `sn-resolve-site-context:sn-app`;
 * 2. the tenant — only when the site is in Tenant mode, bound from the route's
 *    {tenant} parameter and narrowed by the site's tenant type.
 *
 * Both are written to SiteContext once, so downstream code has a single source.
 * Registered as a Livewire persistent middleware: updates hit /livewire/update
 * without the original route, but Livewire replays the original path/method.
 */
class ResolveSiteContext
{
    public function __construct(protected SiteRegistry $registry) {}

    public function handle($request, Closure $next, ?string $pluginId = null): Response
    {
        $site = $pluginId !== null
            ? $this->soleSiteFor($pluginId)
            : $this->siteByRouteName($request);

        if ($site instanceof Site) {
            app(SiteContext::class)->set($site, $this->resolveTenant($request, $site));
        }

        return $next($request);
    }

    protected function siteByRouteName($request): ?Site
    {
        $routeName = $request->route()?->getName();

        return $routeName ? $this->registry->matchByRouteNamePrefix($routeName) : null;
    }

    /** The plugin's registered site; ambiguous (multi-site) usage is a route-naming problem. */
    protected function soleSiteFor(string $pluginId): ?Site
    {
        $sites = $this->registry->forPlugin($pluginId);

        if (count($sites) > 1) {
            throw new RuntimeException(sprintf(
                'Plugin [%s] registered %d sites; route-name matching (no middleware parameter) is required to pick one.',
                $pluginId,
                count($sites)
            ));
        }

        return $sites === [] ? null : reset($sites);
    }

    /** Tenant of a Tenant-mode site; null for Platform and Aggregate sites. */
    protected function resolveTenant($request, Site $site): ?Model
    {
        if (! $site->mode->isTenant()) {
            return null;
        }

        $value = $request->route()?->parameter('tenant');

        if ($value instanceof Model) {
            return $value;
        }

        if (blank($value)) {
            throw InvalidSiteException::tenantRequired($site->id());
        }

        $model = SupportUtils::getTenantModel();

        if ($model === null) {
            throw InvalidSiteException::tenantRequired($site->id());
        }

        $tenant = app($model)->newQuery()
            ->when($site->tenantType !== null, fn ($query) => $query->where('type', $site->tenantType))
            ->where('slug', $value)
            ->first();

        if ($tenant === null) {
            throw (new ModelNotFoundException)->setModel($model, [$value]);
        }

        return $tenant;
    }
}
