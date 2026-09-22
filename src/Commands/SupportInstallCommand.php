<?php

namespace Wsmallnews\Support\Commands;

use Wsmallnews\Support\Concerns\Install\ThirdPartyPublishes;

class SupportInstallCommand extends PackageInstallCommand
{
    use ThirdPartyPublishes;

    protected string $packageName = 'sn-support';

    /**
     * support 管理的第三方依赖发布（config + migrations）
     */
    protected function afterPublish(): void
    {
        $this->publishThirdParty($this, [
            // Media Library
            ['provider' => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider', 'tag' => 'medialibrary-config', 'label' => 'media-library config'],
            ['provider' => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider', 'tag' => 'medialibrary-migrations', 'label' => 'media-library migrations'],
            // Laravel Settings
            ['provider' => 'Spatie\\LaravelSettings\\LaravelSettingsServiceProvider', 'tag' => 'config', 'label' => 'settings config'],
            ['provider' => 'Spatie\\LaravelSettings\\LaravelSettingsServiceProvider', 'tag' => 'migrations', 'label' => 'settings migrations'],
            // Activity Log
            ['provider' => 'Spatie\\Activitylog\\ActivitylogServiceProvider', 'tag' => 'activitylog-config', 'label' => 'activitylog config'],
            ['provider' => 'Spatie\\Activitylog\\ActivitylogServiceProvider', 'tag' => 'activitylog-migrations', 'label' => 'activitylog migrations'],
        ]);
    }
}
