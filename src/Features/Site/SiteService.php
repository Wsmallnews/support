<?php

namespace Wsmallnews\Support\Features\Site;

/**
 * One unit of per-site work (frontend routes, search sources, feed streams…).
 *
 * Services are registered once per site from {@see SiteServicePipeline}; they
 * receive the site explicitly and must not read SiteContext at registration.
 */
abstract class SiteService
{
    /** Whether this service applies to the given site mode (aggregate is read-only). */
    public function supports(SiteMode $mode): bool
    {
        return true;
    }

    abstract public function register(Site $site): void;

    /** Called once after every site has been registered (e.g. aggregate endpoints). */
    public function finish(): void {}
}
