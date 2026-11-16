<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Exceptions\InvalidSiteException;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Site-bound component: keeps the frontend site across Livewire updates and
 * reads its scope from the site context.
 *
 * The route middleware only runs on the initial request; updates hit
 * /livewire/update. The site context middleware is persistent, so it replays
 * the original route there — the snapshot below is the fallback for the case
 * where Livewire cannot match the original route and silently skips middleware.
 *
 * Scope resolution: the bound site's own scope, or the sub-scope its class
 * declares via getScopeSuffix(). A component lives inside a site, and rendering
 * one without a site is a development error.
 *
 * `$scopeable` is an explicit override for cross-scope embedding: it only feeds
 * getScopeable() (writes and explicit forScopeable() queries). Queries through
 * siteContext() always follow the bound site.
 */
trait ScopedBySite
{
    #[Locked]
    public ?string $siteId = null;

    #[Locked]
    public ?int $tenantId = null;

    /** Explicit scope override for embedding; null follows the site context. */
    #[Locked]
    public ?array $scopeable = null;

    /** The site bound to this component: request context first, snapshot second. */
    public function site(): ?Site
    {
        return app(SiteContext::class)->site()
            ?? ($this->siteId ? app(SiteRegistry::class)->get($this->siteId) : null);
    }

    public function mountScopedBySite(): void
    {
        $context = app(SiteContext::class);

        $this->siteId = $context->site()?->id();
        $this->tenantId = $context->tenant()?->getKey();
    }

    /**
     * Re-bind the context when the persistent middleware was skipped (Livewire
     * returns no middleware when the original route no longer matches).
     */
    public function hydrateScopedBySite(): void
    {
        if ($this->siteId === null || app(SiteContext::class)->has()) {
            return;
        }

        $site = app(SiteRegistry::class)->get($this->siteId);

        if ($site === null) {
            return;
        }

        $tenant = null;
        if ($site->mode->isTenant() && $this->tenantId !== null) {
            $tenantModel = SupportUtils::getTenantModel();

            $tenant = $tenantModel !== null ? app($tenantModel)->find($this->tenantId) : null;
        }

        app(SiteContext::class)->set($site, $tenant);
    }

    /**
     * Effective scopeable for reads and writes: the bound site's scope, or the
     * sub-scope declared by the component class.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public function getScopeable(): array
    {
        if ($this->scopeable !== null) {
            return $this->scopeable;
        }

        $context = app(SiteContext::class);
        $site = $this->site() ?? throw InvalidSiteException::noCurrentSite('component scope');
        $suffix = $this->resolveScopeSuffix();

        // Normal path: the request bound a site, so its resolved scopeable wins.
        // Aggregate sites only have one here — their own scopeable is null.
        if ($context->has()) {
            return $context->scopeableOrDefault($suffix);
        }

        // Snapshot fallback: resolve directly from the site record.
        if ($suffix === null) {
            return $site->scopeable() ?? throw InvalidScopeException::missingSiteScopeable($site->id());
        }

        return $site->getScope($suffix)
            ?? throw InvalidScopeException::unknownScopeSuffix($site->id(), $suffix, array_keys($site->getScopes()));
    }

    /** Convenience getters over getScopeable(). */
    public function getScopeType(): string
    {
        return $this->getScopeable()['scope_type'];
    }

    public function getScopeId(): int
    {
        return $this->getScopeable()['scope_id'];
    }

    /** Default sub-scope suffix: components may declare a static getScopeSuffix(). */
    protected function resolveScopeSuffix(): ?string
    {
        return method_exists(static::class, 'getScopeSuffix') ? static::getScopeSuffix() : null;
    }
}
