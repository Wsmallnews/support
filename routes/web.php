<?php

use Illuminate\Support\Facades\Route;
use Wsmallnews\Support\Http\Controllers\FeedController;
use Wsmallnews\Support\Http\Controllers\SitemapController;
use Wsmallnews\Support\Http\Middleware\IdentifyTenant;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/*
|--------------------------------------------------------------------------
| Support Routes
|--------------------------------------------------------------------------
|
| Site-level endpoints (sitemap.xml, robots.txt, feed). Modules only register
| content sources with the registries; the endpoints live here.
|
*/

// Root-level endpoints: no domain restriction and no module prefix, so a site with a
// single module still exposes them.
$middlewares = ['web'];

// Tenant identification when tenancy is enabled; both domain and path modes are attempted
SupportUtils::isTenancyEnabled() && array_unshift($middlewares, IdentifyTenant::class);

Route::middleware($middlewares)->group(function (): void {
    // sitemap.xml / robots.txt（config sn-support.sitemap.*）
    if (SupportUtils::getSitemapConfig('enabled', true) === true) {
        Route::get(SupportUtils::getSitemapConfig('sitemap_uri', 'sitemap.xml'), [SitemapController::class, 'sitemap'])->name('sn-support.sitemap');
        Route::get(SupportUtils::getSitemapConfig('robots_uri', 'robots.txt'), [SitemapController::class, 'robots'])->name('sn-support.robots');
    }

    // RSS feeds (config sn-support.feeds.*): /feed aggregate plus /feed/{name}
    if (SupportUtils::getFeedsConfig('enabled', true) === true) {
        $uri = trim((string) SupportUtils::getFeedsConfig('uri', 'feed'), '/');

        Route::get($uri, [FeedController::class, 'index'])->name('sn-support.feed');
        Route::get($uri . '/{name}', [FeedController::class, 'show'])
            ->where('name', '[a-z0-9\-]+')
            ->name('sn-support.feed.show');
    }
});
