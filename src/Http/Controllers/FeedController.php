<?php

namespace Wsmallnews\Support\Http\Controllers;

use Illuminate\Http\Response;
use Wsmallnews\Support\Facades\Feed;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;

/**
 * Module feed endpoints ({prefix}/feed and {prefix}/feed/{name}).
 *
 * Routes are registered by Feed::routes() with two defaults: feed_module (the owning
 * module) and feed_site (the site id). Both are asserted against the resolved context,
 * so a route reused outside its site fails loudly instead of serving an empty feed.
 */
class FeedController
{
    /** Module aggregate: merges only that module's visible feeds. */
    public function moduleIndex(): Response
    {
        return $this->render();
    }

    /** Named feed; 404 when the name is unknown on this site. */
    public function moduleShow(): Response
    {
        return $this->render((string) request()->route()->parameter('name'));
    }

    protected function render(?string $name = null): Response
    {
        $route = request()->route();
        $module = (string) $route->parameter('feed_module');
        $site = app(SiteRegistry::class)->get((string) $route->parameter('feed_site'));

        if (! $site instanceof Site) {
            abort(404);
        }

        // sn-resolve-site must have bound the same site the route was registered for.
        if (app(SiteContext::class)->site()?->id() !== $site->id()) {
            abort(404);
        }

        if ($name !== null && Feed::getFeed($name, $module, $site) === null) {
            abort(404);
        }

        return $this->response(Feed::render($module, $site, $name)->toHtml());
    }

    protected function response(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
