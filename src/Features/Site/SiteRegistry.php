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

        // Check the address before mutating, so a rejected registration leaves no trace.
        $address = $site->routable ? $this->addressOf($site) : null;

        if ($address !== null && isset($this->addresses[$address])) {
            throw InvalidSiteException::duplicateAddress($id, $this->addresses[$address], $address);
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

    /**
     * Resolve a site by its base scope type (longest match), ignoring aggregate sites.
     *
     * @deprecated Read the site from the page/resource configuration instead of guessing by scope.
     */
    public function findByScopeType(string $scopeType, ?string $panelId = null): ?Site
    {
        $matched = null;

        foreach ($this->sites as $site) {
            if ($site->isAggregate() || ($panelId !== null && $site->panelId !== $panelId)) {
                continue;
            }

            $base = $site->scopeType;

            if (blank($base)) {
                continue;
            }

            if ($scopeType !== $base && ! str_starts_with($scopeType, "{$base}-") && ! str_starts_with($scopeType, "{$base}::")) {
                continue;
            }

            if ($matched === null || strlen($base) > strlen((string) $matched->scopeType)) {
                $matched = $site;
            }
        }

        return $matched;
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
        $this->scopeables = [];
    }

    /** Frontend identity of a site: a null domain matches every host. */
    private function addressOf(Site $site): string
    {
        $domain = strtolower(trim((string) $site->domain));

        return ($domain === '' ? '*' : $domain) . '|' . trim($site->prefix, '/');
    }
}
