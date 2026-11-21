<?php

namespace Wsmallnews\Support\Features\Site;

use Closure;
use InvalidArgumentException;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;

/**
 * Per-site route extensions.
 *
 * A module owns its frontend route group, and therefore its group structure
 * (public / guest / auth, or whatever else it declares). It exposes named slots
 * and calls apply() inside the matching group; support only stores callbacks by
 * (module, slot) and runs them per site, so the site's prefix, domain,
 * middleware and route-name prefix are inherited from that group.
 *
 * Slots are opaque strings: support never validates their names, because the
 * vocabulary belongs to the module that owns the group.
 */
class SiteRouteRegistry
{
    /**
     * @var array<string, array<string, list<Closure>>> module => slot => callbacks
     */
    protected array $extensions = [];

    /**
     * @var array<string, list<string>> module => slots applied at least once
     */
    protected array $applied = [];

    /**
     * Extend one slot of a module's site route group.
     *
     * Call it from a service provider's boot(); every callback receives the
     * current Site and runs once per registered site, inside that site's group.
     */
    public function extend(string $module, string $slot, Closure $callback): static
    {
        if (! ModuleRegistry::has($module)) {
            throw new InvalidArgumentException(
                "Site route extension targets unknown module [{$module}]: register the module descriptor first."
            );
        }

        $this->extensions[$module][$slot][] = $callback;

        return $this;
    }

    /** Run every callback registered for one slot; the module calls this inside its route group. */
    public function apply(string $module, Site $site, string $slot): void
    {
        $this->applied[$module][] = $slot;

        foreach ($this->extensions[$module][$slot] ?? [] as $callback) {
            app()->call($callback, ['site' => $site]);
        }
    }

    /**
     * Slots applied at least once, so a module can assert its own wiring.
     *
     * @return list<string>
     */
    public function appliedSlots(string $module): array
    {
        return array_values(array_unique($this->applied[$module] ?? []));
    }

    /** Drop registered callbacks (all modules, or one) — for tests. */
    public function flush(?string $module = null): static
    {
        if ($module === null) {
            $this->extensions = [];
            $this->applied = [];

            return $this;
        }

        unset($this->extensions[$module], $this->applied[$module]);

        return $this;
    }
}
