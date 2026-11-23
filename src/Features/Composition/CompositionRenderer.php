<?php

namespace Wsmallnews\Support\Features\Composition;

use Illuminate\Support\Arr;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Support\Utils;

/**
 * Turns a composition's row layout into a renderable component list.
 * Types resolve through CompositionRegistry by module id, which is orthogonal to scope.
 */
class CompositionRenderer
{
    /** Row layouts: three-column grid from lg up, single stacked column below. */
    public const LAYOUT_FULL = 'full';          // full width

    public const LAYOUT_LEFT_NARROW = '1-2';    // narrow left, wide right

    public const LAYOUT_LEFT_WIDE = '2-1';      // wide left, narrow right

    public const LAYOUTS = [
        self::LAYOUT_FULL,
        self::LAYOUT_LEFT_NARROW,
        self::LAYOUT_LEFT_WIDE,
    ];

    /** Purpose slot positions; callers use them to place the sidebar column. */
    public const POSITION_LEFT = 'left';

    public const POSITION_RIGHT = 'right';

    public const POSITION_TOP = 'top';

    public const POSITION_BOTTOM = 'bottom';

    /** Standard positions; labels use sn-support::composition.position.{value}. */
    public const POSITIONS = [
        self::POSITION_LEFT,
        self::POSITION_RIGHT,
        self::POSITION_TOP,
        self::POSITION_BOTTOM,
    ];

    /** Editing layout modes: stack (single column sidebar) or rows (full-width, split optional). */
    public const LAYOUT_MODE_STACK = 'stack';

    public const LAYOUT_MODE_ROWS = 'rows';

    /**
     * Resolve a composition bound to a purpose slot, e.g. a detail-page sidebar.
     *
     * Returns null when nothing matches, or when the matched composition resolves to no
     * rows, so callers can fall back to their default layout.
     *
     * The composition's partition and the page's partition are independent inputs:
     * detail pages of several post partitions may SHARE one base composition, while each
     * page still resolves its own current object for the slot context. Both default to
     * the SITE base — never to each other.
     *
     * @param  string  $purpose  Purpose slot, e.g. post-sidebar
     * @param  string  $module  Module id, the CompositionRegistry key
     * @param  array{scope_type: string, scope_id: int}|null  $compositionScopeable  Partition
     *        the slot composition is stored in; null resolves to the site base (pages that
     *        want a partition of their own pass it explicitly)
     * @param  array  $params  Route parameters handed to the slot context provider
     * @param  Site|null  $site  Rendering site; type suffixes resolve against it, and both
     *                          defaulted scopeables come from its base. Reaches providers
     *                          as-is, so one that reads data returns nothing when it is null
     * @param  array{scope_type: string, scope_id: int}|null  $pageScopeable  Partition the
     *        calling page shows its object in; the slot context resolves that object here.
     *        Null resolves to the site base; when even that is unavailable the context
     *        closure receives null and returns nothing — the composition's partition is
     *        never borrowed
     * @return array|null{rows: array, position: string} Null when unresolved
     *
     * @throws InvalidScopeException when no composition partition can be resolved
     */
    public static function resolveForPurpose(string $purpose, string $module, ?array $compositionScopeable = null, array $params = [], ?Site $site = null, ?array $pageScopeable = null): ?array
    {
        $baseScopeable = $site?->scopeable();

        $compositionScopeable ??= $baseScopeable
            ?? throw InvalidScopeException::missingScopeable('the slot composition partition is unknown: pass $compositionScopeable or a site to resolve its base from');

        $composition = Utils::getCompositionModel()::query()
            ->published()
            ->where('purpose', $purpose)
            ->forScopeable($compositionScopeable)
            ->orderByDesc('order_column')
            ->first();

        if (! $composition) {
            return null;
        }

        $meta = CompositionRegistry::getPurpose($module, $purpose);

        // pageContext: slot provider maps route params; null values mean "not found", drop them.
        // It resolves the object the CALLING PAGE is showing: the page's partition, or the
        // site base — never the composition's partition, or a shared base composition
        // would make an article page resolve its current post from the base partition.
        $pageContext = [];
        if (is_callable($meta['pageContext'] ?? null)) {
            $pageContext = collect(app()->call($meta['pageContext'], [
                'params' => $params,
                'scopeable' => $pageScopeable ?? $baseScopeable,
                'site' => $site,
            ]) ?? [])->reject(fn ($value) => $value === null)->all();
        }

        $rows = self::resolveRows($composition->components, $module, $pageContext, $site);

        // No component rows is treated as a miss, or the sidebar column stays blank forever
        if ($rows === []) {
            return null;
        }

        // Normalise position against the slot's declared positions, else the standard set
        $position = $composition->options['position'] ?? null;
        if (filled($meta['positions'] ?? null)) {
            $position = in_array($position, array_keys($meta['positions']), true)
                ? $position
                : ($meta['default'] ?? array_key_first($meta['positions']));
        } else {
            $position = in_array($position, self::POSITIONS, true) ? $position : self::POSITION_RIGHT;
        }

        return [
            'rows' => $rows,
            'position' => $position,
        ];
    }

    /**
     * Resolve every row of a composition.
     *
     * @param  array|null  $rows  Composition components
     * @param  string  $module  Module id, the CompositionRegistry key
     * @param  array  $pageContext  Page-level context seed available to every row
     * @param  Site|null  $site  Rendering site; every type's suffixes resolve against it
     * @return array Resolved rows
     */
    public static function resolveRows(?array $rows, string $module, array $pageContext = [], ?Site $site = null): array
    {
        $resolved = [];

        foreach ($rows ?? [] as $row) {
            // Unknown layouts fall back to full width so bad data still renders
            $layout = in_array($row['layout'] ?? null, self::LAYOUTS, true) ? $row['layout'] : self::LAYOUT_FULL;

            // Row context bag: seeded with page context, flows left to right, dropped at row end
            $context = $pageContext;

            $resolved[] = [
                'layout' => $layout,
                'left' => self::resolveSlot($row['left'] ?? [], $module, $context, $site),
                'right' => self::resolveSlot($row['right'] ?? [], $module, $context, $site),
            ];
        }

        return $resolved;
    }

    /**
     * Resolve one slot (left/right), skipping unregistered types. Each item consumes context
     * first, then resolves, then provides so later items in the slot can read the result.
     *
     * @param  array  $context  Row context bag, passed by reference so the left slot feeds the right
     */
    protected static function resolveSlot(array $items, string $module, array &$context, ?Site $site): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? null;
            if (! $type) {
                continue;
            }

            $typeInfo = CompositionRegistry::getType($module, $type);
            if (! $typeInfo) {
                continue;
            }

            // Consume: keys explicitly set in extras are never overwritten
            $extras = $item['extras'] ?? [];
            foreach ($typeInfo['consumes'] ?? [] as $contextKey) {
                if (! array_key_exists($contextKey, $extras) && array_key_exists($contextKey, $context)) {
                    $extras[$contextKey] = $context[$contextKey];
                }
            }

            // Provide: the closure receives the type's OWN declared suffixes (strings, not
            // resolved pairs) plus the rendering site, and resolves whichever data space
            // it needs — a provider cannot know what its consumers will read, so it hands
            // both down. A post-detail of the "article" partition looks its post up there
            // even when the composition itself lives in the base scope.
            // A null site reaches the closure as-is: a provider that reads data returns
            // nothing (silent), a pure-logic provider still runs.
            if (isset($typeInfo['provides'])) {
                $provided = app()->call($typeInfo['provides'], [
                    'extras' => $extras,
                    'site' => $site,
                    ...self::declaredSuffixes($typeInfo),
                ]) ?? [];
                $context = array_merge($context, $provided);
            }

            // The component gets the same two suffixes as props (inside resolveComponents),
            // exactly like a host page would pass them; ScopedBySite resolves them.
            $resolved = array_merge($resolved, self::resolveComponents($typeInfo, [...$item, 'extras' => $extras]));
        }

        return $resolved;
    }

    /**
     * The partition suffixes a type declared, normalised for handing down: a blank
     * `scopeSuffix` stays null (the site base) and blank related suffixes are dropped,
     * so "not declared" never reaches a component or provider as a null entry.
     *
     * @param  array{scopeSuffix?: ?string, relatedScopeSuffix?: array<string, string|null>}  $typeInfo
     * @return array{scopeSuffix: ?string, relatedScopeSuffix: array<string, string>}
     */
    public static function declaredSuffixes(array $typeInfo): array
    {
        return [
            'scopeSuffix' => filled($typeInfo['scopeSuffix'] ?? null) ? $typeInfo['scopeSuffix'] : null,
            'relatedScopeSuffix' => array_filter(
                $typeInfo['relatedScopeSuffix'] ?? [],
                fn ($suffix) => filled($suffix),
            ),
        ];
    }

    /**
     * Data space a suffix names on a site: the base scope when blank, otherwise the
     * registered partition. An undeclared suffix throws — a type may only name
     * partitions its site registered.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    public static function scopeOfSuffix(Site $site, ?string $suffix): array
    {
        if (blank($suffix)) {
            return $site->scopeable()
                ?? throw InvalidScopeException::missingSiteScopeable($site->id());
        }

        return $site->getScope($suffix)
            ?? throw InvalidScopeException::unknownScopeSuffix($site->id(), $suffix, array_keys($site->getScopes()));
    }

    /** Resolve the registered Livewire components of a type, merging registered defaults with row extras. */
    protected static function resolveComponents(array $typeInfo, array $item): array
    {
        $currentComponents = Arr::wrap($typeInfo['components'] ?? $typeInfo['component'] ?? []);

        $extras = $item['extras'] ?? [];
        $extras['componentInfo'] = [
            'type' => $item['type'],
            'label' => $item['label'] ?? null,
            'description' => $item['description'] ?? null,
            'show_header' => (bool) ($item['show_header'] ?? true),
        ];

        // Forwarded to `contained`; only components using CanBeContained react to it
        $extras['contained'] = (bool) ($item['contained'] ?? true);

        // `embedded` tells components to yield page-level duties (e.g. SEO) to the routed page
        $extras['embedded'] = true;

        // Partition suffixes are the type's identity, declared at registration and handed to
        // the component exactly like a host page would (ScopedBySite: suffix only, never a
        // full pair). Spread last so they override anything stored in the composition data —
        // an edited row must not be able to retarget a component at another partition.
        $extras = [...$extras, ...self::declaredSuffixes($typeInfo)];

        $mapped = Arr::map($currentComponents, function ($currentComponent, $key) use ($extras) {
            if (is_scalar($currentComponent)) {
                return [
                    'component_name' => $currentComponent,
                    'extras' => $extras,
                ];
            }

            return [
                'component_name' => $key,
                'extras' => array_merge($currentComponent, $extras),
            ];
        });

        return array_values($mapped);
    }
}
