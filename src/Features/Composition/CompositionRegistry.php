<?php

namespace Wsmallnews\Support\Features\Composition;

use Closure;
use Illuminate\Support\Collection;

/**
 * Registry of composable content types and purpose slots, keyed by module (plugin id).
 * Types flow left to right within a row; see the Composition section of the README.
 */
class CompositionRegistry
{
    /**
     * @var Collection<string, Collection<string, array>> module => (type => typeInfo)
     */
    protected Collection $modules;

    /**
     * Normalised purpose meta keys: label, positions, default, layout_mode, context.
     *
     * @var Collection<string, Collection<string, array>> module => (purpose => meta)
     */
    protected Collection $purposes;

    public function __construct()
    {
        $this->modules = collect();
        $this->purposes = collect();
    }

    /**
     * @param  string  $module  Module id (plugin id)
     * @param  array  $typeInfo  Type definition, must contain a `type` key
     */
    public function register(string $module, array $typeInfo): static
    {
        $types = $this->getTypes($module);
        $type = $typeInfo['type'];

        $this->modules->put($module, $types->put($type, $typeInfo));

        return $this;
    }

    /**
     * @param  string  $module  Module id
     * @param  array  $typeInfos  List of type definitions
     */
    public function registers(string $module, array $typeInfos): static
    {
        foreach ($typeInfos as $typeInfo) {
            $this->register($module, $typeInfo);
        }

        return $this;
    }

    /** All registered modules. */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * @return Collection<string, array> type => definition for the module
     */
    public function getTypes(string $module): Collection
    {
        return $this->modules->get($module, collect());
    }

    /**
     * @return array|null Type definition, null when unregistered
     */
    public function getType(string $module, string $type): ?array
    {
        return $this->getTypes($module)->firstWhere('type', $type);

    }

    /**
     * @return array<string, string> type => label, for select options
     */
    public function getTypesOptions(string $module): array
    {
        return $this->getTypes($module)->mapWithKeys(function ($typeInfo) {
            return [$typeInfo['type'] => $typeInfo['label']];
        })->toArray();
    }

    /**
    * Re-registering a purpose replaces the array form; the string form only overrides its label.
    * Prefer closure labels (fn () => __('...')) so translation happens at consumption time.
    *
    * @param  string  $module  Module id
    * @param  array<string, string|array{label: string|Closure, positions?: array, default?: string, layout_mode?: string, context?: Closure}>  $purposes
    */
    public function registerPurposes(string $module, array $purposes): static
    {
        $metas = $this->getPurposesMeta($module);

        foreach ($purposes as $purpose => $meta) {
            if (is_string($meta) && ($existing = $metas->get($purpose))) {
                $metas->put($purpose, [...$existing, 'label' => $meta]);

                continue;
            }

            $metas->put($purpose, $this->normalizePurposeMeta(is_string($meta) ? ['label' => $meta] : $meta));
        }

        $this->purposes->put($module, $metas);

        return $this;
    }

    /**
     * @return Collection<string, string> purpose => resolved label
     *
     * @return Collection<string, string>
     */
    public function getPurposes(string $module): Collection
    {
        return $this->getPurposesMeta($module)->map(fn (array $meta) => $this->resolveLabel($meta['label']));
    }

    /**
    * Purpose meta with label/positions already resolved to strings.
    *
    * @return array|null{label: string, positions: array<string, string>, default: ?string, layout_mode: ?string, context: ?Closure} Null when unregistered
    */
    public function getPurpose(string $module, string $purpose): ?array
    {
        $meta = $this->getPurposesMeta($module)->get($purpose);

        if (! $meta) {
            return null;
        }

        return [...$meta,
            'label' => $this->resolveLabel($meta['label']),
            'positions' => collect($meta['positions'])->map(fn ($spec) => $this->resolveLabel($spec))->all(),
        ];
    }

    /** Explicit layout_mode wins; otherwise derived from position (left/right = stack, else rows). */
    public function getLayoutMode(string $module, ?string $purpose, ?string $position): string
    {
        $meta = filled($purpose) ? $this->getPurpose($module, $purpose) : null;

        if (filled($meta['layout_mode'] ?? null) && in_array($meta['layout_mode'], [CompositionRenderer::LAYOUT_MODE_STACK, CompositionRenderer::LAYOUT_MODE_ROWS], true)) {
            return $meta['layout_mode'];
        }

        if (blank($meta)) {
            return CompositionRenderer::LAYOUT_MODE_ROWS;
        }

        $position ??= $meta['default'];

        return in_array($position, [CompositionRenderer::POSITION_LEFT, CompositionRenderer::POSITION_RIGHT], true)
            ? CompositionRenderer::LAYOUT_MODE_STACK
            : CompositionRenderer::LAYOUT_MODE_ROWS;
    }

    protected function getPurposesMeta(string $module): Collection
    {
        return $this->purposes->get($module, collect());
    }

    /** Closures run at consumption time to avoid cross-package boot-order races. */
    protected function resolveLabel(string | Closure $spec): string
    {
        return $spec instanceof Closure ? (string) app()->call($spec) : __($spec);
    }

    /**
     * positions accepts a plain list (labels auto-generated) or a mixed value => label map.
     *
     * @param  array{label: string|Closure, positions?: array, default?: string, layout_mode?: string, context?: Closure}  $meta
     * @return array{label: string|Closure, positions: array<string, string|Closure>, default: ?string, layout_mode: ?string, context: ?Closure}
     */
    protected function normalizePurposeMeta(array $meta): array
    {
        $positions = collect($meta['positions'] ?? [
            CompositionRenderer::POSITION_LEFT,
            CompositionRenderer::POSITION_RIGHT,
        ])
            ->mapWithKeys(function ($label, $key) {
                $value = is_int($key) ? $label : $key;

                return [$value => is_int($key) ? $this->standardPositionLabel($value) : $label];
            })
            ->all();

        return [
            'label' => $meta['label'],
            'positions' => $positions,
            'default' => $meta['default'] ?? array_key_first($positions),
            'layout_mode' => $meta['layout_mode'] ?? null,
            'context' => $meta['context'] ?? null,
        ];
    }

    /** Standard positions get a lazily translated label; custom values keep their own. */
    protected function standardPositionLabel(string $value): Closure
    {
        return in_array($value, CompositionRenderer::POSITIONS, true)
            ? fn (): string => __("sn-support::composition.position.{$value}")
            : fn (): string => $value;
    }

    /**
     * @param  string  $module  Module id
     * @param  string  $type  Type key
     * @param  array  $arguments  Passed to the closure form when applicable
     */
    public function hasTypeForms(string $module, string $type, array $arguments = []): bool
    {
        $forms = $this->getTypeForms($module, $type, $arguments);

        return $forms && count($forms) > 0;
    }

    /**
     * @param  string  $module  Module id
     * @param  string  $type  Type key
     * @param  array  $arguments  Passed to the closure form when applicable
     * @return array Form schema
     */
    public function getTypeForms(string $module, string $type, array $arguments = []): array
    {
        $typeInfo = $this->getType($module, $type);

        $forms = $typeInfo['forms'] ?? [];

        return $forms instanceof Closure ? app()->call($forms, $arguments) : $forms;
    }
}
