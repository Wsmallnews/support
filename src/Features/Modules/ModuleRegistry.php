<?php

namespace Wsmallnews\Support\Features\Modules;

use Filament\Contracts\Plugin;
use RuntimeException;

/**
 * Registry of module identities: reverse lookup by namespace, existence checks and metadata.
 * Static by design (written at boot, read per request); flush() resets it in tests.
 */
class ModuleRegistry
{
    /**
     * @var array<string, Module> module id => descriptor
     */
    protected static array $modules = [];

    /**
     * Namespace roots sorted longest first, for moduleOf().
     *
     * @var array<string, string>|null namespace => module id
     */
    protected static ?array $namespaceIndex = null;

    /** Register a module; re-registering the same id overwrites, keeping boot idempotent. */
    public static function register(Module $module): void
    {
        self::$modules[$module->id] = $module;
        self::$namespaceIndex = null;
    }

    /** Whether a module is registered. */
    public static function has(string $id): bool
    {
        return isset(self::$modules[$id]);
    }

    /** Fetch a module descriptor. */
    public static function get(string $id): ?Module
    {
        return self::$modules[$id] ?? null;
    }

    /**
     * Every registered module.
     *
     * @return array<string, Module>
     */
    public static function all(): array
    {
        return self::$modules;
    }

    /** Module id owning a class or object, matched by longest namespace prefix. */
    public static function moduleOf(string | object $class): ?string
    {
        $class = is_object($class) ? $class::class : $class;

        if (blank(self::$namespaceIndex)) {
            self::$namespaceIndex = [];

            foreach (self::$modules as $module) {
                self::$namespaceIndex[rtrim($module->namespace, '\\')] = $module->id;
            }

            // Longest namespace first: Wsmallnews\Cms\Livewire beats Wsmallnews\Cms
            uksort(self::$namespaceIndex, fn (string $a, string $b) => strlen($b) <=> strlen($a));
        }

        foreach (self::$namespaceIndex as $namespace => $id) {
            if ($class === $namespace || str_starts_with($class, $namespace . '\\')) {
                return $id;
            }
        }

        return null;
    }

    /** The module's Filament plugin instance, or null when unregistered or plugin-less. */
    public static function plugin(string $id): ?Plugin
    {
        $plugin = self::get($id)?->plugin;

        return filled($plugin) && class_exists($plugin) ? app($plugin) : null;
    }

    /** Assert a module is registered, throwing otherwise; registries use it to catch typos. */
    public static function require(string $id): Module
    {
        return self::get($id) ?? throw new RuntimeException(
            "Module [{$id}] is not registered in ModuleRegistry: please register a Module descriptor in the package ServiceProvider's packageRegistered() method."
        );
    }

    /** Reset the registry (tests only). */
    public static function flush(): void
    {
        self::$modules = [];
        self::$namespaceIndex = null;
    }
}
