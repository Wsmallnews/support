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
            // 如果地址栏中没有 租户标识,则会跳过校验 (需要保证所有租户请求,包含租户标识,否则视为平台 请求)
            return $next($request);
        }

        $tenantId = $request->route()->parameter('tenant');
        $tenant = $this->getTenant($tenantId);

        // livewire/update 请求时 request() 和 $request 不是同一个实例,这里统一用 request() (应该是和持久化 livewire 中间件有关系 https://livewire.laravel.com/docs/3.x/security#middleware)
        request()->attributes->set('has_tenancy', true);
        request()->attributes->set('current_tenant', $tenant);

        return $next($request);
    }

    /**
     * 通过 id 获取租户
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
