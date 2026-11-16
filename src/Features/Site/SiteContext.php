<?php

namespace Wsmallnews\Support\Features\Site;

use Filament\Facades\Filament;
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

        // The site mode decides whether a tenant participates at all: Platform and
        // Aggregate sites must never carry one, whatever the caller passes.
        $this->tenant = $site->mode->isTenant() ? $tenant : null;

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
     * The site scope wins; an unresolved scope fails closed. There is no
     * fallback: components live inside sites, and code running without a site
     * context (console, queue) passes an explicit scopeable to
     * applyDeclaredScope() instead of relying on implicit derivation.
     *
     * @return array{scope_type: string, scope_id: int}
     *
     * @throws InvalidScopeException when no site is bound, or a bound site has
     *                               no resolved scopeable, or the suffix is unknown
     */
    public function scopeableOrDefault(?string $suffix = null): array
    {
        if ($this->site === null) {
            throw InvalidSiteException::noCurrentSite('scope resolution');
        }

        if ($this->scopeable === []) {
            throw InvalidScopeException::invalidConfiguration(
                $this->site->id(),
                'the bound site has no resolved scopeable',
            );
        }

        if (blank($suffix)) {
            return $this->scopeable;
        }

        // Sub-scopes come from the map built at registration: an explicit
        // override on the component may differ from a naive derivation.
        return $this->site->getScope($suffix)
            ?? throw InvalidScopeException::unknownScopeSuffix(
                $this->site->id(),
                $suffix,
                array_keys($this->site->scopes),
            );
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
     * Frontend query: the site's own scope (or a registered sub-scope) plus the
     * site mode rule — Tenant fails closed without a tenant, Platform only reads
     * tenant-less rows, Aggregate reads every tenant's rows of the scope.
     *
     * Rendering without a bound site is a development error: pass an explicit
     * scopeable via applyDeclaredScope() when the caller owns the conditions.
     *
     * @throws InvalidSiteException when no site is bound
     */
    public function applySiteScope(Builder $query, ?string $suffix = null): Builder
    {
        $scopeable = $this->scopeableOrDefault($suffix);

        $query->where('scope_type', $scopeable['scope_type'])
            ->where('scope_id', $scopeable['scope_id']);

        return $this->applyMode($query);
    }

    /**
     * Explicit-scope query: the caller knows the data space (panel declaration,
     * a record's own scope, a console/queue batch).
     *
     * With a site: scope plus the site mode rule. Without one: scope plus the
     * tenant rule of the current context (panel tenancy / platform rows).
     * Pass $withTenant = false for a record-relation query where the parent
     * already fixes the tenant: the scope pair is then the only condition.
     *
     * @param  array{scope_type: string, scope_id: int}  $scopeable
     *
     * @throws InvalidScopeException when the scopeable is empty or has no scope type
     */
    public function applyDeclaredScope(Builder $query, array $scopeable, bool $withTenant = true): Builder
    {
        if (blank($scopeable['scope_type'] ?? null)) {
            throw InvalidScopeException::missingScopeable(
                'the explicit query has no scope_type (a component without a declared scope?)'
            );
        }

        $query->where('scope_type', $scopeable['scope_type'])
            ->where('scope_id', $scopeable['scope_id'] ?? 0);

        if (! $withTenant) {
            return $query;
        }

        if ($this->site) {
            return $this->applyMode($query);
        }

        // A tenancy panel already scopes every resource model through a global scope
        // named after the panel; adding ours would duplicate the same predicate.
        // Resource queries pass withTenant: false themselves — a resource with
        // isScopedToTenant(false) has no such global scope but must stay unfiltered.
        $panel = Filament::getCurrentPanel();

        if ($panel?->hasTenancy() && $query->getModel()::hasGlobalScope($panel->getTenancyScopeName())) {
            return $query;
        }

        return $query->scopeTenant();
    }

    /** Site ownership rule: who owns the rows this site may read. */
    protected function applyMode(Builder $query): Builder
    {
        return match ($this->site->mode) {
            SiteMode::Tenant => $this->tenant
                ? $query->where('team_id', $this->tenant->getKey())
                : $query->whereRaw('1 = 0'),
            SiteMode::Platform => $query->whereNull('team_id'),
            SiteMode::Aggregate => $query->whereNotNull('team_id'),
        };
    }
}
