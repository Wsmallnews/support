<?php

namespace Wsmallnews\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wsmallnews\Support\Features\Seo\Seo;

/**
 * 初始化页面 SEO 上下文：重建 SeoPage 并声明模块归属。
 *
 * 普通路由中间件（勿加入 Livewire 持久化清单）：只在首屏请求运行，与 <head> 的
 * 输出时机一一对应；Livewire update 请求不经过页面路由中间件、也不重渲染 layout，
 * 无需也无从重新初始化。
 *
 * 用法（模块名作参数，路由即模块边界的权威声明）：
 *
 *     Route::middleware('seo-init:sn-cms')->...
 */
class InitializeSeo
{
    public function handle(Request $request, Closure $next, ?string $module = null): mixed
    {
        app(Seo::class)->init($module);

        return $next($request);
    }
}
