<?php

namespace Wsmallnews\Support\Features\Site;

/**
 * Runs a fixed list of {@see SiteService} instances over every registered site.
 *
 * The outer loop is the site, so a service can accumulate cross-site data and
 * flush it in finish().
 */
final class SiteServicePipeline
{
    /**
     * @param  list<SiteService>  $services
     */
    public function __construct(protected array $services) {}

    /**
     * @param  iterable<Site>  $sites
     */
    public function registerAll(iterable $sites): void
    {
        foreach ($sites as $site) {
            foreach ($this->services as $service) {
                if ($service->supports($site->mode)) {
                    $service->register($site);
                }
            }
        }
    }

    public function finish(): void
    {
        foreach ($this->services as $service) {
            $service->finish();
        }
    }
}
