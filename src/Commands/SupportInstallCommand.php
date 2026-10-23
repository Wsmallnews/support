<?php

namespace Wsmallnews\Support\Commands;

use Wsmallnews\Support\Concerns\Install\ThirdPartyPublishes;

class SupportInstallCommand extends PackageInstallCommand
{
    use ThirdPartyPublishes;

    protected string $packageName = 'sn-support';

    /** Publish the third-party resources managed by support. */
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
