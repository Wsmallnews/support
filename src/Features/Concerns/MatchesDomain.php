<?php

namespace Wsmallnews\Support\Features\Concerns;

use Illuminate\Support\Collection;

/**
 * Domain filtering shared by SitemapRegistry and FeedRegistry. Consumers must provide
 * $configs (module => options, with a domain key) and isDomainFilterEnabled().
 *
 * @property Collection<string, array<string, mixed>> $configs
 */
trait MatchesDomain
{
    /** Whether domain filtering is on (default true). */
    abstract protected function isDomainFilterEnabled(): bool;

    /**
     * Whether a module takes part in the current output: filtering off, no declared domain,
     * or a domain matching the request.
     */
    protected function moduleMatchesDomain(string $module): bool
    {
        if (! $this->isDomainFilterEnabled()) {
            return true;
        }

        $domain = $this->configs->get($module, [])['domain'] ?? null;

        return blank($domain) || $this->hostMatches($domain, request()->getHost());
    }

    /**
     * Match the request host against an exact domain or a route pattern such as
     * {tenant:slug}.example.com, where placeholders match any dot-free segment.
     */
    protected function hostMatches(string $pattern, string $host): bool
    {
        if (strcasecmp($pattern, $host) === 0) {
            return true;
        }

        if (! str_contains($pattern, '{')) {
            return false;
        }

        $regex = str_replace("\x01", '[^.]+', preg_quote(preg_replace('/\{[^}]+\}/', "\x01", $pattern), '/'));

        return (bool) preg_match('/^' . $regex . '$/i', $host);
    }
}
