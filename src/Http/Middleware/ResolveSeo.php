<?php

namespace Wsmallnews\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wsmallnews\Support\Features\Seo\Seo;

/**
 * Rebuild the page SEO context for the current request. A normal route middleware, never a
 * persistent Livewire one: only first renders output <head>, and update requests skip it.
 * Usage: Route::middleware('sn-resolve-seo:sn-cms').
 */
class ResolveSeo
{
    public function handle(Request $request, Closure $next, ?string $module = null): mixed
    {
        app(Seo::class)->init($module);

        return $next($request);
    }
}
