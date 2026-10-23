<?php

namespace Wsmallnews\Support\Features\Composition;

use Illuminate\Support\Arr;
use Wsmallnews\Support\Facades\CompositionRegistry;
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
    * @param  string  $purpose  Purpose slot, e.g. post-sidebar
    * @param  string  $module  Module id, the CompositionRegistry key
    * @param  string  $scopeType  The module's main scope type
    * @param  int  $scopeId  The module's main scope id
    * @param  array  $params  Route parameters handed to the slot context provider
    * @return array|null{rows: array, position: string} Null when unresolved
    */
    public static function resolveForPurpose(string $purpose, string $module, string $scopeType, int $scopeId = 0, array $params = []): ?array
    {
        $composition = Utils::getCompositionModel()::query()
            ->published()
            ->where('purpose', $purpose)
            ->snScope($scopeType, $scopeId)
            ->orderByDesc('order_column')
            ->first();

        if (! $composition) {
            return null;
        }

        $meta = CompositionRegistry::getPurpose($module, $purpose);

        // pageContext: slot provider maps route params; null values mean "not found", drop them
        $pageContext = [];
        if (is_callable($meta['context'] ?? null)) {
            $pageContext = collect(app()->call($meta['context'], [
                'params' => $params,
                'scopeable' => ['scope_type' => $scopeType, 'scope_id' => $scopeId],
            ]) ?? [])->reject(fn ($value) => $value === null)->all();
        }

        $rows = self::resolveRows($composition->components, $module, $pageContext);

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
    * @return array Resolved rows
    */
    public static function resolveRows(?array $rows, string $module, array $pageContext = []): array
    {
        $resolved = [];

        foreach ($rows ?? [] as $row) {
            // Unknown layouts fall back to full width so bad data still renders
            $layout = in_array($row['layout'] ?? null, self::LAYOUTS, true) ? $row['layout'] : self::LAYOUT_FULL;

            // Row context bag: seeded with page context, flows left to right, dropped at row end
            $context = $pageContext;

            $resolved[] = [
                'layout' => $layout,
                'left' => self::resolveSlot($row['left'] ?? [], $module, $context),
                'right' => self::resolveSlot($row['right'] ?? [], $module, $context),
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
    protected static function resolveSlot(array $items, string $module, array &$context): array
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
            foreach ($typeInfo['context'] ?? [] as $contextKey) {
                if (! array_key_exists($contextKey, $extras) && array_key_exists($contextKey, $context)) {
                    $extras[$contextKey] = $context[$contextKey];
                }
            }

            $resolved = array_merge($resolved, self::resolveComponents($typeInfo, [...$item, 'extras' => $extras]));

            // Provide: the closure receives the injected extras and merges its result into the bag
            if (isset($typeInfo['provides'])) {
                $provided = app()->call($typeInfo['provides'], ['extras' => $extras]) ?? [];
                $context = array_merge($context, $provided);
            }
        }

        return $resolved;
    }

    /** Resolve the Livewire components of a type, merging registered defaults with row extras. */
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
