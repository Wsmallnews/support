<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;
use RuntimeException;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Exceptions\InvalidSiteException;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Site-bound component: keeps the frontend site across Livewire updates, reads
 * its scope from the site context, and reads site options with the owning
 * module's config as fallback.
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
 * `$scopeSuffix` selects a partition of the bound site (null = the site's own
 * scope). The value must name a sub-scope declared at registration, so a
 * component can never reach another site's rows. A class that declares its own
 * suffix is bound to that partition: passing a different suffix fails instead
 * of silently retargeting the component.
 */
trait ScopedBySite
{
    #[Locked]
    public ?string $siteId = null;

    #[Locked]
    public ?int $tenantId = null;

    /** Sub-scope suffix of the bound site; null follows the site's own scope. */
    #[Locked]
    public ?string $scopeSuffix = null;

    /** Related resources keyed by relation name; each value is a site sub-scope suffix. */
    #[Locked]
    public array $relatedScopeSuffix = [];

    /** The site bound to this component: request context first, snapshot second. */
    public function site(): ?Site
    {
        return app(SiteContext::class)->site()
            ?? ($this->siteId ? app(SiteRegistry::class)->get($this->siteId) : null);
    }

    /**
     * Site option: site options first, then a module's config, then $default.
     *
     * $owner picks the middle step:
     * - null: this component's own module (getOwnerModule())
     * - false: no module fallback, $default wins (components belonging to no module)
     * - string: that module's config
     *
     * The site itself already falls back to its own module's options, so $owner
     * only covers components reused on another module's site (cms posts on a
     * shop site): their defaults still come from cms.
     */
    public function siteOption(string $path, mixed $default = null, string | false | null $owner = null): mixed
    {
        $value = $this->site()?->getOption($path);

        if ($value !== null) {
            return $value;
        }

        if ($owner === false) {
            return $default;
        }

        return config(($owner ?? $this->getOwnerModule()) . ".options.{$path}", $default);
    }

    /** Owner module of this component, detected from ModuleRegistry by namespace. */
    public function getOwnerModule(): string
    {
        return ModuleRegistry::moduleOf(static::class)
            ?? throw new RuntimeException(sprintf(
                'Component [%s] belongs to no registered module; register one in the package ServiceProvider.',
                static::class
            ));
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

    /** Site scope without this component's own ::suffix. */
    public function getBaseScopeable(): array
    {
        $scopeable = $this->getScopeable();
        $separator = strpos($scopeable['scope_type'], '::');

        return $separator === false
            ? $scopeable
            : [
                'scope_type' => substr($scopeable['scope_type'], 0, $separator),
                'scope_id' => $scopeable['scope_id'],
            ];
    }

    /**
     * Data space of one related resource: the site's base scope plus the suffix
     * declared for that relation, or the base scope when none is declared.
     *
     * $relatedScopeSuffix is the frontend counterpart of the backend
     * 'related_scope_suffix' map: keys are relation names, values are suffixes.
     * A suffix must name a sub-scope declared at registration — an unknown one
     * fails instead of silently querying an empty partition.
     */
    public function getRelatedScopeable(string $related): array
    {
        $suffix = $this->relatedScopeSuffix[$related] ?? null;

        if (blank($suffix)) {
            return $this->getBaseScopeable();
        }

        $site = $this->site() ?? throw InvalidSiteException::noCurrentSite('related scope');

        return $site->getScope((string) $suffix)
            ?? throw InvalidScopeException::unknownScopeSuffix($site->id(), (string) $suffix, array_keys($site->getScopes()));
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

    /**
     * Effective sub-scope suffix: the instance selection, or the class declaration.
     *
     * A declared suffix is the component identity, so an instance may only repeat
     * it — selecting another partition is a wiring error, not an override.
     */
    protected function resolveScopeSuffix(): ?string
    {
        $declared = method_exists(static::class, 'getScopeSuffix') ? static::getScopeSuffix() : null;
        $declared = filled($declared) ? (string) $declared : null;
        $given = filled($this->scopeSuffix) ? (string) $this->scopeSuffix : null;

        if ($declared !== null && $given !== null && $given !== $declared) {
            throw InvalidScopeException::scopeSuffixConflict(static::class, $declared, $given);
        }

        return $given ?? $declared;
    }
}
