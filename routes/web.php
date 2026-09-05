<?php

use Illuminate\Support\Facades\Route;
use Wsmallnews\Support\Http\Controllers\SitemapController;
use Wsmallnews\Support\Http\Middleware\IdentifyTenant;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/*
|--------------------------------------------------------------------------
| Support Routes
|--------------------------------------------------------------------------
|
| support 包的通用路由文件，后续包级路由在此追加。当前承载站点级 SEO 端点。
|
*/

// 站点级 SEO 端点（sitemap.xml / robots.txt）：注册在根路径、不限定域名也不参与任何
// 模块前缀——cms / shop 等模块只是往 SitemapRegistry 注册内容源与 robots 规则，
// 端点本身由 support 提供（所有模块都依赖 support，单装任一模块站点也具备）
if (config('sn-support.sitemap.enabled', true) === true) {
    $middlewares = ['web'];

    // 多租户开启时，挂租户识别（域名/路径模式均尽力解析，无租户参数时安全跳过）
    SupportUtils::isTenancyEnabled() && array_unshift($middlewares, IdentifyTenant::class);

    Route::middleware($middlewares)->group(function () {
        Route::get(config('sn-support.sitemap.sitemap_uri', 'sitemap.xml'), [SitemapController::class, 'sitemap'])->name('sn-support.sitemap');
        Route::get(config('sn-support.sitemap.robots_uri', 'robots.txt'), [SitemapController::class, 'robots'])->name('sn-support.robots');
    });
}
