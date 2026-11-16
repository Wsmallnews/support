<?php

namespace Wsmallnews\Support\Models\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteMode;

/**
 * Data-ownership query scopes, split by axis:
 *
 * - site axis   (scope_type + scope_id): siteContext() / panelContext() / forScopeable()
 * - tenant axis (team_id):               scopeTenant() / forTenant() / allTenants() / tenantAggregate()
 *
 * The site axis needs both scope columns; the tenant axis only needs team_id, so
 * tables without a data space (e.g. the media library) use the tenant methods only.
 */
trait SiteScope
{
    /**
     * Scope this record belongs to.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function getScopeable(): array
    {
        return ['scope_type' => $this->scope_type, 'scope_id' => $this->scope_id];
    }

    /** Scope type of this record. */
    public function getScopeType(): string
    {
        return $this->scope_type;
    }

    /** Scope id of this record. */
    public function getScopeId(): int
    {
        return $this->scope_id;
    }

    /**
     * Frontend query: current site's own scope, or one of its registered
     * sub-scopes. Rendering without a bound site fails closed.
     *
     * @throws InvalidScopeException when called inside a panel without a site
     */
    public function scopeSiteContext(Builder $query, ?string $subScope = null): Builder
    {
        // A panel has no site: the correct entry point there is panelContext().
        // A site bound inside a panel (tests, embedding) is still honoured.
        if (is_in_panel() && ! app(SiteContext::class)->has()) {
            throw InvalidScopeException::siteContextInPanel(static::class);
        }

        return app(SiteContext::class)->applySiteScope($query, $subScope);
    }

    /**
     * Panel query: the scope comes from the registration declaration, so it is
     * mandatory and the call is only valid inside a panel.
     *
     * @param  array{scope_type: string, scope_id: int}  $scopeable
     *
     * @throws InvalidScopeException
     */
    public function scopePanelContext(Builder $query, array $scopeable): Builder
    {
        // Fails fast outside a panel: a frontend query must derive its scope
        // from the site, and a console/queue batch must use forScopeable().
        if (! is_in_panel()) {
            throw InvalidScopeException::panelContextOutsidePanel();
        }

        return app(SiteContext::class)->applyDeclaredScope($query, $scopeable);
    }

    /**
     * Explicit scope, valid in every context: a panel declaration, a record's
     * own scope, or a console/queue batch.
     *
     * The context tenant rule applies by default. Pass $withTenant = false in a
     * record-relation query where the parent already fixes the tenant (pay
     * records of an order, views of a preference record): the scope pair is
     * then the only condition, and console/queue reads keep working.
     *
     * @param  array{scope_type: string, scope_id: int}  $scopeable
     *
     * @throws InvalidScopeException
     */
    public function scopeForScopeable(Builder $query, array $scopeable, bool $withTenant = true): Builder
    {
        return app(SiteContext::class)->applyDeclaredScope($query, $scopeable, $withTenant);
    }

    /**
     * Tenant axis, strict: rows of the current tenant in this context.
     *
     * A site decides by its mode (Tenant / Platform / Aggregate). A panel decides
     * by its tenancy: disabled reads platform rows, enabled without an active
     * tenant fails closed. No context (console/queue) reads platform rows —
     * tenant semantics there require an explicit forTenant() or SiteContext::run().
     */
    public function scopeScopeTenant(Builder $query): Builder
    {
        $context = app(SiteContext::class);

        if ($site = $context->site()) {
            $tenant = $context->tenant();

            return match ($site->mode) {
                SiteMode::Tenant => $tenant
                    ? $query->where('team_id', $tenant->getKey())
                    : $query->whereRaw('1 = 0'),
                SiteMode::Platform => $query->whereNull('team_id'),
                SiteMode::Aggregate => $query->whereNotNull('team_id'),
            };
        }

        if (is_in_panel()) {
            if (! Filament::getCurrentPanel()?->hasTenancy()) {
                return $query->whereNull('team_id');
            }

            $tenant = Filament::getTenant();

            return $tenant
                ? $query->where('team_id', $tenant->getKey())
                : $query->whereRaw('1 = 0');
        }

        return $query->whereNull('team_id');
    }

    /** Explicit tenant; null keeps only tenant-less (platform) rows. */
    public function scopeForTenant(Builder $query, int | string | null $tenantId): Builder
    {
        return $tenantId === null
            ? $query->whereNull('team_id')
            : $query->where('team_id', $tenantId);
    }

    /** Cross-tenant view: every row that belongs to a tenant, excluding platform (null) rows. */
    public function scopeTenantAggregate(Builder $query): Builder
    {
        return $query->whereNotNull('team_id');
    }

    /** Escape hatch: cross-tenant / system query without any tenant condition. */
    public function scopeAllTenants(Builder $query): Builder
    {
        return $query;
    }
}
