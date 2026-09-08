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
| support 包的通用路由文件，后续包级路由在此追加。当前承载站点级端点
| （sitemap.xml / robots.txt / feed）。
|
*/

// 站点级端点：注册在根路径、不限定域名也不参与任何模块前缀——cms / shop 等模块只是
// 往各 Registry（SitemapRegistry / FeedRegistry）注册内容源/流，端点本身由 support
// 提供（所有模块都依赖 support，单装任一模块站点也具备）
$middlewares = ['web'];

// 多租户开启时，挂租户识别（域名/路径模式均尽力解析，无租户参数时安全跳过）
SupportUtils::isTenancyEnabled() && array_unshift($middlewares, IdentifyTenant::class);

Route::middleware($middlewares)->group(function (): void {
    // sitemap.xml / robots.txt（config sn-support.sitemap.*）
    if (SupportUtils::getSitemapConfig('enabled', true) === true) {
        Route::get(SupportUtils::getSitemapConfig('sitemap_uri', 'sitemap.xml'), [SitemapController::class, 'sitemap'])->name('sn-support.sitemap');
        Route::get(SupportUtils::getSitemapConfig('robots_uri', 'robots.txt'), [SitemapController::class, 'robots'])->name('sn-support.robots');
    }

    // RSS 订阅（config sn-support.feeds.*）：/feed 聚合流 + /feed/{name} 具名流
    if (SupportUtils::getFeedsConfig('enabled', true) === true) {
        $uri = trim((string) SupportUtils::getFeedsConfig('uri', 'feed'), '/');

        Route::get($uri, [FeedController::class, 'index'])->name('sn-support.feed');
        Route::get($uri . '/{name}', [FeedController::class, 'show'])
            ->where('name', '[a-z0-9\-]+')
            ->name('sn-support.feed.show');
    }
});
