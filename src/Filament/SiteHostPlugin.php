<?php

namespace Wsmallnews\Support\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use InvalidArgumentException;
use Wsmallnews\Support\Features\Site\SiteConfig;
use Wsmallnews\Support\Features\Site\SiteRegistry;

/**
 * Zero-opinion site host for panels that have no site-owning plugin of their
 * own (third parties consuming ability packages without cms/shop).
 *
 * Declares one or more sites on the panel; nothing else is registered — no
 * resources, no frontend routes, no site services. Ability-package resources
 * are listed by the consumer directly on the panel and derive their scope
 * from the sites declared here.
 *
 *     ->plugins([
 *         SiteHostPlugin::make()->site('main', fn (SiteConfig $site) => $site
 *             ->scopeType('sn-app')
 *             ->routable(true)->prefix('app'),   // only when serving frontend components
 *         ),
 *     ])
 *
 * Frontend routes that must bind the site add `sn-resolve-site-context:sn-host`
 * (the plugin id) — route names stay untouched.
 */
class SiteHostPlugin implements Plugin
{
    /** @var array<string, SiteConfig> */
    protected array $siteConfigs = [];

    public function getId(): string
    {
        return 'sn-site-host';
    }

    /**
     * Declare a site on this host. The configure closure MUST set an explicit
     * scopeType — there is no plugin id to derive one from.
     */
    public function site(string $key, ?\Closure $configure = null): static
    {
        $site = new SiteConfig($this->getId(), '', $key);

        if ($configure !== null) {
            $configure($site);
        }

        if (blank($site->getScopeType())) {
            throw new InvalidArgumentException(
                "SiteHostPlugin site [{$key}] requires an explicit scopeType: there is no plugin id to derive one from."
            );
        }

        $this->siteConfigs[$key] = $site;

        return $this;
    }

    public function register(Panel $panel): void
    {
        if ($this->siteConfigs === []) {
            throw new InvalidArgumentException(
                'SiteHostPlugin requires at least one explicit site(). Example: '
                . 'SiteHostPlugin::make()->site("main", fn (SiteConfig $site) => $site->scopeType("sn-app"))'
            );
        }

        // Resolve + register into SiteRegistry (panel id filled from $panel).
        foreach ($this->siteConfigs as $config) {
            $config->panel($panel->getId());

            if ($config->getMode() === null) {
                // Panel hosts default to platform mode; tenant hosts use their own plugin.
                $config->mode('platform');
            }

            app(SiteRegistry::class)->register($config->toSite());
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
