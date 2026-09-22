<?php

namespace Wsmallnews\Support\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Wsmallnews\Support\Features\Install\InstallPlanner;

/**
 * 扩展包安装命令基类：依赖编排 + 统一交互 + 美化输出。
 *
 * 子类只需声明 protected string $packageName（如 'sn-cms'），
 * 可选覆盖 afterPublish() 追加包私有发布步骤（如 settings 迁移）。
 *
 * 执行流（顶层运行）：
 *   1. 生成依赖安装计划（InstallPlanner：composer 拓扑序，每包一次）
 *   2. 逐个静默安装依赖（依赖模式：只发布，不询问）
 *   3. 发布自身资源（config / migrations / afterPublish 钩子）
 *   4. 统一询问并执行一次 migrate（跑全部待迁移项）
 *   5. 只对当前包询问 GitHub star（依赖包不询问）
 *
 * 选项：
 *   --no-deps         跳过依赖安装
 *   --as-dependency   （内部）作为依赖被调用：仅发布资源，无任何交互
 */
abstract class PackageInstallCommand extends InstallCommand
{
    /**
     * 模块 id（= 配置名 = 命令前缀，如 sn-cms）
     */
    protected string $packageName = '';

    /**
     * star 的仓库（默认 wsmallnews/{模块短名}）
     */
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

    /**
     * 包私有发布步骤（在标准 config/migrations 之后执行）
     */
    protected function afterPublish(): void {}

    /**
     * 发布自身资源：config + migrations + afterPublish 钩子（task 风格输出）
     */
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
     * 安装单个依赖（静默模式：仅发布资源，无交互）
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

    /**
     * 统一迁移询问：只问一次，执行全局 migrate（跑全部待迁移项）；非交互环境跳过
     */
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

    /**
     * star 询问：只问当前安装的包（依赖包不询问）；非交互环境跳过
     */
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

    /**
     * 小节标题（── sn-cms ──────，ASCII 分隔线避免 Windows 终端编码乱码）
     */
    protected function section(string $title): void
    {
        $line = substr('  ' . $title . ' ' . str_repeat('-', 56), 0, 58);

        $this->line("<fg=gray>{$line}</>");
    }
}
