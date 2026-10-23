<?php

namespace Wsmallnews\Support\Http\Controllers;

use Illuminate\Http\Response;
use Wsmallnews\Support\Facades\Sitemap;
use Wsmallnews\Support\Features\Sitemap\SitemapRegistry;

/**
 * Site-level SEO endpoints. Both routes sit at the root without a domain restriction, so
 * every domain bound to the app can reach them, and robots always points at the current host.
 */
class SitemapController
{
    /** Aggregated sitemap; SitemapRegistry caches the result per tenant. */
    public function sitemap(): Response
    {
        return response(Sitemap::render()->toHtml(), 200, [
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
