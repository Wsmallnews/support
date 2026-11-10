<?php

namespace Wsmallnews\Support\Features\Site;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Exceptions\InvalidSiteException;

/**
 * Request-scoped site context, written by the ResolveSite middleware or by
 * {@see self::run()} in console/queue jobs.
 *
 * Components and services read the effective scopeable / mode from here instead
 * of resolving static config themselves. Bound as a scoped singleton, so it is
 * reset per request (Octane-safe).
 */
final class SiteContext
{
    private ?Site $site = null;

    private ?Model $tenant = null;

    /** @var array{scope_type: string, scope_id: int}|array{} */
    private array $scopeable = [];

    public function set(Site $site, ?Model $tenant = null, array $scopeable = []): void
    {
        $this->site = $site;
        $this->tenant = $tenant;
        $this->scopeable = $scopeable ?: ($site->scopeable() ?? app(SiteRegistry::class)->scopeableFor($site));
    }

    public function reset(): void
    {
        $this->site = null;
        $this->tenant = null;
        $this->scopeable = [];
    }

    /**
     * Run a callback with the given site bound, restoring the previous context
     * afterwards (including on failure). Safe to nest.
     */
    public function run(Site $site, callable $callback, ?Model $tenant = null): mixed
    {
        $previous = [$this->site, $this->tenant, $this->scopeable];

        $this->set($site, $tenant);

        try {
            return $callback($site, $this);
        } finally {
            [$this->site, $this->tenant, $this->scopeable] = $previous;
        }
    }

    public function has(): bool
    {
        return $this->site !== null;
    }

    public function site(): ?Site
    {
        return $this->site;
    }

    /** Current site or fail loudly — for callers that cannot proceed without one. */
    public function requireSite(): Site
    {
        return $this->site
            ?? throw InvalidSiteException::noCurrentSite('read');
    }

    public function mode(): ?SiteMode
    {
        return $this->site?->mode;
    }

    /** @return array{scope_type: string, scope_id: int}|array{} */
    public function scopeable(): array
    {
        return $this->scopeable;
    }

    /**
     * Effective module scope for the current request.
     *
     * With a resolved site, the site scope wins and an unresolved site fails
     * closed (no silent fallback to the module default). Without a site context
     * (console, queue, seeders) the module default is used.
     *
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException when a site is bound but has no resolved scope
     */
    public function scopeableOrDefault(string $defaultType, ?string $suffix = null): array
    {
        if ($this->scopeable === []) {
            if ($this->site !== null) {
                throw InvalidScopeException::invalidConfiguration(
                    $this->site->id(),
                    'the bound site has no resolved scopeable',
                );
            }

            $scopeable = ['scope_type' => $defaultType, 'scope_id' => 0];
        } else {
            $scopeable = $this->scopeable;
        }

        if (blank($suffix)) {
            return $scopeable;
        }

        // With a site, sub-scopes come from the map built at registration: an
        // explicit override on the component may differ from a naive derivation.
        if ($this->site !== null) {
            return $this->site->getScope($suffix)
                ?? throw InvalidScopeException::unknownScopeSuffix(
                    $this->site->id(),
                    $suffix,
                    array_keys($this->site->scopes),
                );
        }

        // Console / queue: no site, derive from the module default.
        $scopeable['scope_type'] = "{$scopeable['scope_type']}::{$suffix}";

        return $scopeable;
    }

    public function tenant(): ?Model
    {
        return $this->tenant;
    }

    public function tenantId(): int | string | null
    {
        return $this->tenant?->getKey();
    }

    /** Guard write paths before touching models inside an aggregate view. */
    public function assertWritable(): void
    {
        if ($this->site?->isReadOnly()) {
            throw InvalidSiteException::readOnly($this->site->id());
        }
    }

    /**
     * Apply the site's scopeable and tenant condition to a query.
     *
     * Tenant mode fails closed when no tenant is resolved; platform only reads
     * tenant-less rows; aggregate reads every tenant's rows of the same scope.
     */
    public function applyScope(Builder $query): Builder
    {
        if (! $this->site) {
            return $query;
        }

        if ($this->scopeable !== []) {
            $query->where('scope_type', $this->scopeable['scope_type'])
                ->where('scope_id', $this->scopeable['scope_id']);
        }

        return match ($this->site->mode) {
            SiteMode::Tenant => $this->tenant
                ? $query->where('team_id', $this->tenant->getKey())
                : $query->whereRaw('1 = 0'),
            SiteMode::Platform => $query->whereNull('team_id'),
            SiteMode::Aggregate => $query->whereNotNull('team_id'),
        };
    }
}
