<?php

namespace Wsmallnews\Support\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Wsmallnews\Support\Features\Install\InstallPlanner;

/**
 * Base install command for extension packages: dependency planning, prompts and output.
 * Subclasses declare $packageName and may override afterPublish().
 * See the Install section of the README.
 */
abstract class PackageInstallCommand extends InstallCommand
{
    /** Module id, used as config name and command prefix (e.g. sn-cms). */
    protected string $packageName = '';

    /** Repository to star; defaults to wsmallnews/{short name}. */
    protected ?string $starRepoName = null;

    public function __construct(Package $package)
    {
        $package->name($this->packageName);

        parent::__construct($package);

        $this->signature = "{$this->packageName}:install
                            {--no-deps : Install without dependencies}
                            {--as-dependency : (internal) Run as a dependency: publish only, no prompts}";
        $this->description = "Install the {$this->packageName} package";
        $this->hidden = false;

        $this->configureUsingFluentDefinition();
        $this->specifyParameters();

        $this->publishConfigFile();
        $this->publishMigrations();
    }

    public function handle()
    {
        $asDependency = (bool) $this->option('as-dependency');
        $noDeps = (bool) $this->option('no-deps');

        if (! $asDependency) {
            $this->components->info("Installing {$this->packageName}");

            $plan = $noDeps ? collect() : InstallPlanner::plan($this->packageName);

            if ($plan->isNotEmpty()) {
                $this->newLine();
                $this->components->twoColumnDetail(
                    '<fg=gray>Dependency plan</>',
                    $plan->map(fn (array $item) => $item['name'])->implode(' ➜ ')
                );
            }

            foreach ($plan as $dependency) {
                $this->installDependency($dependency);
            }
        }

        if (! $asDependency) {
            $this->newLine();
            $this->section($this->packageName);
        }

        $this->publishResources();

        if ($asDependency) {
            return self::SUCCESS;
        }

        $this->askMigrationsOnce();
        $this->askStarOnce();

        $this->newLine();
        $this->components->info("{$this->packageName} has been installed!");

        return self::SUCCESS;
    }

    /** Package-specific publish steps, run after config and migrations. */
    protected function afterPublish(): void {}

    /** Publish this package's resources: config, migrations, then the afterPublish hook. */
    protected function publishResources(): void
    {
        foreach ($this->publishes as $tag) {
            $label = 'Publishing ' . str_replace('-', ' ', $tag);

            $this->components->task($label, function () use ($tag) {
                return Artisan::call('vendor:publish', [
                    '--tag' => "{$this->package->shortName()}-{$tag}",
                    '--no-interaction' => true,
                ]) === self::SUCCESS;
            });
        }

        $this->afterPublish();
    }

    /**
     * Install one dependency silently: publish only, no prompts.
     *
     * @param  array{name: string, command: string}  $dependency
     */
    protected function installDependency(array $dependency): void
    {
        $this->newLine();
        $this->section("{$dependency['name']}  <fg=gray>(dependency)</>");

        Artisan::call($dependency['command'], [
            '--as-dependency' => true,
            '--no-deps' => true,
            '--no-interaction' => true,
        ], $this->getOutput());
    }

    /** Ask once, then run a single migrate for every pending migration. Non-interactive runs skip it. */
    protected function askMigrationsOnce(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $this->newLine();

        if ($this->confirm('Would you like to run the database migrations now?')) {
            $this->components->task('Running migrations', function () {
                return Artisan::call('migrate', ['--no-interaction' => true]) === self::SUCCESS;
            });
        }
    }

    /** Ask only for the package being installed, never for dependencies. Non-interactive runs skip it. */
    protected function askStarOnce(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        $repo = $this->starRepoName ?? 'wsmallnews/' . Str::after($this->packageName, 'sn-');

        if (! $this->confirm('Would you like to show some love by starring our repo on GitHub?')) {
            return;
        }

        $repoUrl = "https://github.com/{$repo}";

        match (PHP_OS_FAMILY) {
            'Darwin' => exec("open {$repoUrl}"),
            'Windows' => exec("start {$repoUrl}"),
            'Linux' => exec("xdg-open {$repoUrl}"),
            default => null,
        };
    }

    /** Section heading; ASCII rules avoid encoding issues on Windows terminals. */
    protected function section(string $title): void
    {
        $line = substr('  ' . $title . ' ' . str_repeat('-', 56), 0, 58);

        $this->line("<fg=gray>{$line}</>");
    }
}
