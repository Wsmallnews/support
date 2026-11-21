<?php

namespace Wsmallnews\Support\Features\Site;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
    /**
     * How each auth user type becomes the current identity (member, user, store, ...).
     *
     * Support cannot depend on those modules, so every module registers its own
     * resolver; the site's `auth.user_type` option picks one at read time.
     *
     * @var array<string, Closure(Site, string): ?Model>
     */
    private static array $authUserResolvers = [];

    private ?Site $site = null;

    private ?Model $tenant = null;

    /** @var array{scope_type: string, scope_id: int}|array{} */
    private array $scopeable = [];

    /** Identity override for this request (previews, tests); null means "resolve lazily". */
    private ?Model $authUser = null;

    private bool $authUserResolved = false;

    /** Register the resolver of one auth user type; later registrations replace earlier ones. */
    public static function registerAuthUser(string $type, Closure $resolver): void
    {
        self::$authUserResolvers[$type] = $resolver;
    }

    public function set(Site $site, ?Model $tenant = null, array $scopeable = []): void
    {
        $this->site = $site;

        // The site mode decides whether a tenant participates at all: Platform and
        // Aggregate sites must never carry one, whatever the caller passes.
        $this->tenant = $site->mode->isTenant() ? $tenant : null;

        $this->scopeable = $scopeable ?: ($site->scopeable() ?? app(SiteRegistry::class)->scopeableFor($site));

        // A different site means a different identity; drop the previous resolution.
        $this->authUser = null;
        $this->authUserResolved = false;
    }

    public function reset(): void
    {
        $this->site = null;
        $this->tenant = null;
        $this->scopeable = [];
        $this->authUser = null;
        $this->authUserResolved = false;
    }

    /**
     * Run a callback with the given site bound, restoring the previous context
     * afterwards (including on failure). Safe to nest.
     */
    public function run(Site $site, callable $callback, ?Model $tenant = null): mixed
    {
        $previous = [$this->site, $this->tenant, $this->scopeable, $this->authUser, $this->authUserResolved];

        $this->set($site, $tenant);

        try {
            return $callback($site, $this);
        } finally {
            [$this->site, $this->tenant, $this->scopeable, $this->authUser, $this->authUserResolved] = $previous;
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

    /**
     * Effective option of the current site (site options > the owning module's
     * config). Without a site the default is returned: rendering must never
     * throw just because no site is bound.
     */
    public function option(string $path, mixed $default = null): mixed
    {
        return $this->site?->getOption($path, $default) ?? $default;
    }

    /** Guard the site authenticates with (module default under `auth.guard`). */
    public function getGuard(): string
    {
        return (string) $this->option('auth.guard', 'web');
    }

    /** Auth user type the site expects, e.g. `user` or `member`. */
    public function getAuthUserType(): string
    {
        return (string) $this->option('auth.user_type', 'user');
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

    /** Override the identity of this request; the site's resolver is then skipped. */
    public function authUser(?Model $authUser): void
    {
        $this->authUser = $authUser;
        $this->authUserResolved = true;
    }

    /**
     * Guard user of the current site: `Auth::guard($guard)->user()`.
     *
     * This is always the user model behind the guard. The site's auth subject
     * may be a different model (a member); read that through getAuthUser().
     */
    public function getUser(): ?Model
    {
        // Panels and console have no site: never guess a guard there.
        if ($this->site === null) {
            return null;
        }

        return Auth::guard($this->getGuard())->user();
    }

    /**
     * Current frontend identity, resolved once per request by the site's
     * `auth.user_type` and cached until the context changes.
     *
     * Panels and console have no site: they never guess an identity, so
     * components there must receive the user explicitly.
     *
     * @throws InvalidSiteException when the site names an unregistered type
     */
    public function getAuthUser(): ?Model
    {
        if ($this->site === null) {
            return null;
        }

        if ($this->authUserResolved) {
            return $this->authUser;
        }

        $type = $this->getAuthUserType();
        $resolver = self::$authUserResolvers[$type] ?? null;

        if ($resolver === null) {
            throw InvalidSiteException::unknownAuthUser($type, $this->site->id());
        }

        $this->authUser = $resolver($this->site, $this->getGuard());
        $this->authUserResolved = true;

        return $this->authUser;
    }

    public function hasAuthUser(): bool
    {
        return $this->getAuthUser() !== null;
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
