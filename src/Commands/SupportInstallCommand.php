<?php

namespace Wsmallnews\Support\Commands;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Wsmallnews\Support\Concerns\Install\ThirdPartyPublishes;

class SupportInstallCommand extends InstallCommand
{
    use ThirdPartyPublishes;

    public function __construct(Package $package)
    {
        $package->name('sn-support');

        parent::__construct($package);

        $this->signature = 'sn-support:install
                            {--no-deps : Install without dependencies and skip interactive prompts}';
        $this->description = 'Install sn-support';
        $this->hidden = false;

        $this->configureUsingFluentDefinition();
        $this->specifyParameters();

        $this->publishConfigFile();
        $this->publishMigrations();
        $this->askToRunMigrations();
        $this->askToStarRepoOnGitHub('wsmallnews/support');
    }

    public function handle()
    {
        $noDeps = $this->option('no-deps');
        $isDependency = ! $this->input->isInteractive();

        if ($noDeps || $isDependency) {
            $this->askToRunMigrations = false;
            $this->starRepo = null;
        }

        if (! $noDeps) {
            $this->installThirdParty();
        }

        parent::handle();

        return self::SUCCESS;
    }

    protected function installThirdParty(): void
    {
        $publishes = [
            // Media Library
            ['provider' => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider', 'tag' => 'medialibrary-config', 'label' => 'media-library config'],
            ['provider' => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider', 'tag' => 'medialibrary-migrations', 'label' => 'media-library migrations'],
            // Laravel Settings
            ['provider' => 'Spatie\\LaravelSettings\\LaravelSettingsServiceProvider', 'tag' => 'config', 'label' => 'settings config'],
            ['provider' => 'Spatie\\LaravelSettings\\LaravelSettingsServiceProvider', 'tag' => 'migrations', 'label' => 'settings migrations'],
            // Activity Log
            ['provider' => 'Spatie\\Activitylog\\ActivitylogServiceProvider', 'tag' => 'activitylog-config', 'label' => 'activitylog config'],
            ['provider' => 'Spatie\\Activitylog\\ActivitylogServiceProvider', 'tag' => 'activitylog-migrations', 'label' => 'activitylog migrations'],
        ];

        // 先发布第三方依赖的 配置 和 数据迁移
        $this->publishThirdParty($this, $publishes);
    }
}
