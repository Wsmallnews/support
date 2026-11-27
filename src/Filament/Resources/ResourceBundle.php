<?php

namespace Wsmallnews\Support\Filament\Resources;

use InvalidArgumentException;

/**
 * Declares one package's panel components as a unit, so a site-owning plugin
 * can register the whole package in one call and still partition or trim it.
 *
 * A bundle is only a declaration: toArray() renders plain registration entries
 * in the shape RegistersSites::resources()/pages() accept, and every rule about
 * instances, keys and scope suffixes is enforced by the registering plugin.
 */
class ResourceBundle
{
    /** @var array<class-string, array<string, mixed>> */
    protected array $resources = [];

    /** @var array<class-string, array<string, mixed>> */
    protected array $pages = [];

    /**
     * Related-scope keys each component reads, and the bundle class that
     * carries the data: component => [related key => carrier class].
     *
     * @var array<class-string, array<string, class-string>>
     */
    protected array $relations = [];

    /**
     * Classes left out at registration => the partition they live in, or null
     * for the site's base scope.
     *
     * @var array<class-string, string|null>
     */
    protected array $excluded = [];

    protected ?string $suffix = null;

    protected ?string $key = null;

    public static function make(): static
    {
        return new static;
    }

    /**
     * Resources of the package: class names or `Class::class => overrides`.
     *
     * @param  array<int|string, mixed>  $resources
     */
    public function resources(array $resources): static
    {
        $this->resources = array_merge($this->resources, $this->normalise($resources));

        return $this;
    }

    /**
     * Pages of the package, same shape as resources().
     *
     * @param  array<int|string, mixed>  $pages
     */
    public function pages(array $pages): static
    {
        $this->pages = array_merge($this->pages, $this->normalise($pages));

        return $this;
    }

    /**
     * Declare which related-scope keys a component reads and which bundle
     * class carries that data, e.g. ProductResource reads "extras" from
     * ExtraResource. A partition (suffix()) then follows the bundle.
     *
     * @param  class-string  $component
     * @param  array<string, class-string>  $relations  related key => carrier class
     */
    public function relates(string $component, array $relations): static
    {
        $this->assertInBundle($component);

        foreach ($relations as $carrier) {
            $this->assertInBundle($carrier);
        }

        $this->relations[$component] = array_merge($this->relations[$component] ?? [], $relations);

        return $this;
    }

    /**
     * Put the whole bundle in one partition of the site (scope_suffix), and
     * make every relation carried inside the bundle follow it.
     */
    public function suffix(?string $suffix): static
    {
        if (filled($suffix) && str_contains($suffix, '::')) {
            throw new InvalidArgumentException(
                "Bundle suffix [{$suffix}] is a bare partition name; the site scope is derived by the registering plugin."
            );
        }

        $this->suffix = filled($suffix) ? $suffix : null;

        return $this;
    }

    /**
     * Configuration key of this registration. Required when the same classes
     * are registered more than once on one site.
     */
    public function key(?string $key): static
    {
        $this->key = filled($key) ? $key : null;

        return $this;
    }

    /**
     * Leave classes out at registration. A value names the partition the
     * excluded component lives in, so relations pointing at it are re-routed
     * instead of falling back to the site's base scope.
     *
     * @param  array<class-string, string|null>  $classes
     */
    public function except(array $classes): static
    {
        foreach ($this->normalise($classes, nullableScalarValue: true) as $class => $partition) {
            $this->assertInBundle($class);

            $this->excluded[$class] = $partition;
        }

        return $this;
    }

    /**
     * Accepts class names, `Class::class => overrides`, or with nullable
     * scalar values `Class::class => partition`.
     *
     * @param  array<int|string, mixed>  $components
     * @return array<class-string, mixed>
     */
    protected function normalise(array $components, bool $nullableScalarValue = false): array
    {
        $normalised = [];

        foreach ($components as $key => $value) {
            $class = is_int($key) ? $value : $key;

            if ($nullableScalarValue) {
                // A bare class name carries no partition: only `Class => 'partition'` does.
                $normalised[$class] = (! is_int($key) && is_string($value)) ? $value : null;

                continue;
            }

            $normalised[$class] = is_array($value) ? $value : [];
        }

        return $normalised;
    }

    /**
     * Registration entries in the shape RegistersSites::resources()/pages() accept.
     *
     * @return array{resources: array<int|string, mixed>, pages: array<int|string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'resources' => $this->entries($this->resources),
            'pages' => $this->entries($this->pages),
        ];
    }

    /**
     * @param  array<class-string, array<string, mixed>>  $components
     * @return array<int|string, mixed>
     */
    protected function entries(array $components): array
    {
        $entries = [];

        foreach ($components as $class => $overrides) {
            if (array_key_exists($class, $this->excluded)) {
                continue;
            }

            $overrides = $this->resolveRelations($class, $overrides);

            if ($this->suffix !== null && ! array_key_exists('scope_suffix', $overrides)) {
                $overrides['scope_suffix'] = $this->suffix;
            }

            if ($this->key === null) {
                $entries[$class] = $overrides;

                continue;
            }

            // A second instance of a class must be a tuple: its identity may
            // never collapse into the class's first registration.
            $entries[] = [$class, ['key' => $overrides['key'] ?? $this->key] + $overrides];
        }

        return $entries;
    }

    /**
     * Related-scope map for one component: relations carried inside the bundle
     * follow the bundle partition, relations carried by an excluded class are
     * re-routed to where that class lives. The registration's own declaration
     * and the class-level defaults stay in force for everything else.
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function resolveRelations(string $class, array $overrides): array
    {
        $relations = $this->relations[$class] ?? [];

        if ($relations === []) {
            return $overrides;
        }

        $declared = $overrides['related_scope_suffix'] ?? [];
        $related = array_merge(
            class_exists($class) && method_exists($class, 'getRelatedScopeSuffixes') ? $class::getRelatedScopeSuffixes() : [],
            (array) $declared,
        );

        foreach ($relations as $key => $carrier) {
            // An explicit declaration on this registration wins.
            if (is_array($declared) && array_key_exists($key, $declared)) {
                continue;
            }

            if (array_key_exists($carrier, $this->excluded)) {
                $partition = $this->excluded[$carrier];

                if ($partition === null) {
                    unset($related[$key]);
                } else {
                    $related[$key] = $partition;
                }

                continue;
            }

            if ($this->suffix !== null) {
                $related[$key] = $this->suffix;
            }
        }

        if ($related !== []) {
            $overrides['related_scope_suffix'] = $related;
        }

        return $overrides;
    }

    /** @param  class-string  $class */
    protected function assertInBundle(string $class): void
    {
        if (
            isset($this->resources[$class])
            || isset($this->pages[$class])
        ) {
            return;
        }

        throw new InvalidArgumentException(
            "Component [{$class}] is not part of the bundle; declare it in resources() or pages() first."
        );
    }
}
