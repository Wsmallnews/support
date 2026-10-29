<?php

namespace Wsmallnews\Support\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SupportUtils::isTenancyEnabled()) {
            return $next($request);
        }

        if (! $request->route()->hasParameter('tenant')) {
            // Without a tenant in the URL the check is skipped, so every tenant request must carry one
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
