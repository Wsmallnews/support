<?php

namespace Wsmallnews\Support\Http\Controllers;

use Illuminate\Http\Response;
use Wsmallnews\Support\Facades\Sitemap;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;

/**
 * Root robots.txt. Sitemap endpoints live in each site's route group, so robots
 * advertises every registered frontend site's sitemap.
 */
class SitemapController
{
    /**
     * Render one module's sitemap. The route carries sitemap_module / sitemap_site
     * defaults (see SitemapRegistry::routes); the site must match the one bound by
     * sn-resolve-site-context, otherwise 404.
     */
    public function sitemap(): Response
    {
        $site = app(SiteRegistry::class)->get((string) request()->route()->parameter('sitemap_site'));

        if (! $site instanceof Site) {
            abort(404);
        }

        if (app(SiteContext::class)->site()?->id() !== $site->id()) {
            abort(404);
        }

        $module = (string) request()->route()->parameter('sitemap_module');

        return response(Sitemap::render($module, $site)->toHtml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /** robots.txt: panel paths, registered disallow rules and the Sitemap line. */
    public function robots(): Response
    {
        return response(app(SitemapRegistry::class)->renderRobots(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
