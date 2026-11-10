<?php

namespace Wsmallnews\Support\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Resolve the route's {tenant} parameter into the request attributes consumed by
 * current_tenant(). Must run before sn-resolve-site, which copies the tenant into
 * SiteContext. Also registered as a Livewire persistent middleware.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SupportUtils::isTenancyEnabled()) {
            return $next($request);
        }

        if (! $request->route()?->hasParameter('tenant')) {
            // Without a tenant in the path or domain the check is skipped, so every
            // tenant request must declare {tenant} in its route signature.
            return $next($request);
        }

        $tenantId = $request->route()->parameter('tenant');
        $tenant = $this->getTenant($tenantId);

        // On livewire/update, request() and $request are different instances, so use request()
        request()->attributes->set('has_tenancy', true);
        request()->attributes->set('current_tenant', $tenant);

        return $next($request);
    }

    /**
     * Resolve the tenant by id.
     *
     * @param  int  $tenantId
     * @return void
     */
    protected function getTenant($tenantId)
    {
        $tenantModel = SupportUtils::getTenantModel();

        $record = app($tenantModel)
            ->resolveRouteBinding($tenantId, 'slug');

        if ($record === null) {
            throw (new ModelNotFoundException)->setModel($tenantModel, [$tenantId]);
        }

        return $record;
    }
}
