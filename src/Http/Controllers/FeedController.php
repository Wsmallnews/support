<?php

namespace Wsmallnews\Support\Http\Controllers;

use Illuminate\Http\Response;
use Wsmallnews\Support\Facades\Feed;

/**
 * Site-level RSS endpoints (/feed and /feed/{name}); modules register their own endpoints
 * inside their route group and tag them with ->defaults('feed_module', '<plugin id>').
 */
class FeedController
{
    /** Site aggregate: merges every visible feed, newest first. */
    public function index(): Response
    {
        return $this->response(Feed::render()->toHtml());
    }

    /** Named feed; 404 when unknown or hidden by the domain filter. */
    public function show(string $name): Response
    {
        abort_if(Feed::getFeed($name) === null, 404);

        return $this->response(Feed::render($name)->toHtml());
    }

    /**
     * Module aggregate: merges only that module's visible feeds.
     *
     * feed_module is read from the route rather than injected by position: defaults and URI
     * segments do not line up reliably through positional injection.
     */
    public function moduleIndex(): Response
    {
        $module = (string) request()->route()->parameter('feed_module');

        return $this->response(Feed::renderModule($module)->toHtml());
    }

    /** Module named feed; 404 when the feed belongs to another module or is invisible. */
    public function moduleShow(string $name): Response
    {
        $module = (string) request()->route()->parameter('feed_module');

        abort_if(Feed::getFeed($name, $module) === null, 404);

        return $this->response(Feed::renderModule($module, $name)->toHtml());
    }

    protected function response(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
