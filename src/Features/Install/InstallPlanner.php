<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Features\Install;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use RuntimeException;
use Wsmallnews\Support\Commands\PackageInstallCommand;

/**
 * Builds the install plan from composer itself: walks the target package's wsmallnews
 * requires in vendor, expands them recursively and orders them topologically.
 * Only packages exposing an {sn-xxx}:install command are planned, and the root is excluded.
 */
class InstallPlanner
{
    /**
     * Build the dependency install plan in topological order, root excluded.
     *
     * @param  string  $rootModule  Target module id, e.g. sn-cms
     * @return Collection<int, array{name: string, command: string}> name is the composer package
     */
    public static function plan(string $rootModule): Collection
    {
        $rootComposer = self::composerNameOf($rootModule);

        $visited = [];
        $order = [];

        $visit = function (string $composerName) use (&$visit, &$visited, &$order): void {
            if (isset($visited[$composerName])) {
                return;
            }
            $visited[$composerName] = true;

            foreach (self::dependenciesOf($composerName) as $dependency) {
                $visit($dependency);
            }

            $order[] = $composerName;
        };

        foreach (self::dependenciesOf($rootComposer) as $dependency) {
            $visit($dependency);
        }

        return collect($order)
            ->filter(fn (string $composerName) => filled(self::installCommandOf($composerName)))
            ->map(fn (string $composerName) => [
                'name' => $composerName,
                'command' => self::installCommandOf($composerName),
            ])
            ->values();
    }

    /** Module id to composer package name (sn-cms to wsmallnews/cms). */
    protected static function composerNameOf(string $module): string
    {
        return 'wsmallnews/' . Str::after($module, 'sn-');
    }

    /**
     * Composer package to install command (wsmallnews/cms to sn-cms:install). Only subclasses of
     * PackageInstallCommand qualify; others return null and must be installed on their own.
     */
    protected static function installCommandOf(string $composerName): ?string
    {
        $command = 'sn-' . Str::after($composerName, 'wsmallnews/') . ':install';

        $instance = Artisan::all()[$command] ?? null;

        return $instance instanceof PackageInstallCommand ? $command : null;
    }

    /**
     * Direct wsmallnews requirements of a package, read from its composer.json in vendor.
     *
     * @return array<int, string>
     */
    protected static function dependenciesOf(string $composerName): array
    {
        $manifest = base_path('vendor/' . $composerName . '/composer.json');

        if (! is_file($manifest)) {
            throw new RuntimeException("Package [{$composerName}] is not installed under vendor/: please run composer install first.");
        }

        $requires = json_decode((string) file_get_contents($manifest), true)['require'] ?? [];

        return collect(array_keys($requires))
            ->filter(fn (string $dependency) => str_starts_with($dependency, 'wsmallnews/'))
            ->values()
            ->all();
    }
}
