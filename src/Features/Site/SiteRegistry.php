<?php

namespace Wsmallnews\Support\Features\Site;

use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Exceptions\InvalidSiteException;

/**
 * Global registry of sites, keyed by "{pluginId}:{key}".
 *
 * Populated by Filament plugins while panels are constructed; read by the
 * frontend route registrar and by the ResolveSite middleware. Bound as a
 * singleton in the container and flushed between test cases.
 */
final class SiteRegistry
{
    /** @var array<string, Site> */
    private array $sites = [];

    /** @var array<string, string> frontend address => site id */
    private array $addresses = [];

    /** @var array<string, list<array{domain: ?string, id: string}>> normalized prefix => registrations */
    private array $prefixes = [];

    /** @var array<string, string> "scope_type:scope_id" => site id */
    private array $scopeables = [];

    /**
     * Register one site. Enforces the global invariants that can be checked in
     * isolation: unique id (plugin + key), unique frontend address, one data
     * space per non-aggregate site, no self-referencing aggregate. Aggregate
     * source existence is validated by the plugin while it resolves its sites.
     */
    public function register(Site $site): void
    {
        $id = $site->id();

        if (isset($this->sites[$id])) {
            throw InvalidSiteException::alreadyRegistered($id, $this->sites[$id]->panelId);
        }

        if ($site->isAggregate() && $site->sourceSiteKey === $site->key) {
            throw InvalidSiteException::invalidSource($id, "{$site->pluginId}:{$site->sourceSiteKey}");
        }

        // Address invariants run before mutating, so a rejected registration
        // leaves no trace. Non-routable sites claim no address at all.
        $address = $prefix = $domain = null;

        if ($site->routable) {
            $address = $this->addressOf($site);
            $prefix = trim($site->prefix, '/');
            $domain = strtolower(trim((string) $site->domain)) ?: null;

            if (isset($this->addresses[$address])) {
                throw InvalidSiteException::duplicateAddress($id, $this->addresses[$address], $address);
            }

            // A wildcard domain matches every host, so it would shadow — or be
            // shadowed by, in registration order — any other domain on the same
            // prefix. Exact duplicates are caught above; this guards the overlap.
            foreach ($this->prefixes[$prefix] ?? [] as $registration) {
                if ($domain === null || $registration['domain'] === null || $domain === $registration['domain']) {
                    throw InvalidSiteException::overlappingAddress($id, $registration['id'], $prefix);
                }
            }
        }

        // Two sites writing the same (scope_type, scope_id) would mix their rows,
        // no matter which plugin registered them. Aggregates inherit a source
        // scope and never claim one.
        $pair = $site->isAggregate() ? null : $site->scopeable();
        $signature = $pair === null ? null : "{$pair['scope_type']}:{$pair['scope_id']}";

        if ($signature !== null && isset($this->scopeables[$signature])) {
            throw InvalidSiteException::duplicateScopeable(
                $id,
                $this->scopeables[$signature],
                $pair['scope_type'],
                $pair['scope_id'],
            );
        }

        $this->sites[$id] = $site;

        if ($address !== null) {
            $this->addresses[$address] = $id;
            $this->prefixes[$prefix][] = ['domain' => $domain, 'id' => $id];
        }

        if ($signature !== null) {
            $this->scopeables[$signature] = $id;
        }
    }

    /** @return array<string, Site> */
    public function all(): array
    {
        return $this->sites;
    }

    /** @return array<string, Site> */
    public function forPlugin(string $pluginId): array
    {
        return array_filter(
            $this->sites,
            static fn (Site $site): bool => $site->pluginId === $pluginId,
        );
    }

    /** @return array<string, Site> */
    public function forPanel(string $panelId): array
    {
        return array_filter(
            $this->sites,
            static fn (Site $site): bool => $site->panelId === $panelId,
        );
    }

    public function get(?string $siteId): ?Site
    {
        return $siteId === null ? null : ($this->sites[$siteId] ?? null);
    }

    /** Match a route name to its site by the longest registered route-name prefix. */
    public function matchByRouteNamePrefix(string $routeName): ?Site
    {
        $matched = null;

        foreach ($this->sites as $site) {
            if (! str_starts_with($routeName, $site->routeNamePrefix)) {
                continue;
            }

            if ($matched === null || strlen($site->routeNamePrefix) > strlen($matched->routeNamePrefix)) {
                $matched = $site;
            }
        }

        return $matched;
    }

    /** Source site of an aggregate site; the reference is plugin-internal. */
    public function sourceOf(Site $site): ?Site
    {
        return $site->isAggregate() ? $this->get("{$site->pluginId}:{$site->sourceSiteKey}") : null;
    }

    /**
     * Resolve the effective scopeable of a site. Aggregate sites inherit the
     * source site's scopeable; the source must exist by registration time.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function scopeableFor(Site $site): array
    {
        if (! $site->isAggregate()) {
            return $site->scopeable()
                ?? throw InvalidScopeException::missingSiteScopeable($site->id());
        }

        $source = $this->sourceOf($site);

        if ($source === null) {
            throw InvalidScopeException::missingSourceSite($site->id(), "{$site->pluginId}:{$site->sourceSiteKey}");
        }

        if ($source->isAggregate()) {
            throw InvalidSiteException::invalidSource($site->id(), $source->id());
        }

        return $this->scopeableFor($source);
    }

    public function flush(): void
    {
        $this->sites = [];
        $this->addresses = [];
        $this->prefixes = [];
        $this->scopeables = [];
    }

    /** Frontend identity of a site: a null domain matches every host. */
    private function addressOf(Site $site): string
    {
        $domain = strtolower(trim((string) $site->domain));

        return ($domain === '' ? '*' : $domain) . '|' . trim($site->prefix, '/');
    }
}
