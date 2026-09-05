<?php

namespace Wsmallnews\Support\Http\Controllers;

use Illuminate\Http\Response;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;
use Wsmallnews\Support\Facades\Sitemap;

/**
 * 站点级 SEO 端点：sitemap.xml（聚合各模块注册的 URL 来源）与 robots.txt（爬虫规则）。
 *
 * 路由注册在根路径且不限定域名（config sn-support.sitemap.*）：任何绑定到本应用的
 * 域名/子域名（如 cms.smallnews.top、shop.smallnews.top）都能经由当前域名访问到
 * 这两个端点；robots.txt 的 Sitemap: 行也始终指向当前域名的 sitemap.xml。
 */
class SitemapController
{
    /**
     * sitemap.xml：聚合输出全部模块注册的 URL（结果经 SitemapRegistry 缓存，缓存键携带租户）。
     */
    public function sitemap(): Response
    {
        return response(Sitemap::render()->toHtml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * robots.txt：面板路径自动发现 + 各模块注册的禁爬规则 + Sitemap 行（渲染在 SitemapRegistry）。
     */
    public function robots(): Response
    {
        return response(app(SitemapRegistry::class)->renderRobots(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
