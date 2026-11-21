<?php

namespace Wsmallnews\Support\Tenant\Settings;

use RuntimeException;

/**
 * Asserts the host registered the repositories plugin settings classes resolve
 * rows through. Spatie's factory fails with a bare message when a name is
 * missing, and the drift only surfaces when a settings page is opened —
 * validate at boot instead. Hosts may register any additional repositories
 * alongside; only these names are required.
 */
class SettingsRepositoryGuard
{
    /** @var array<string> */
    private const REQUIRED = ['site_database'];

    public static function assertRegistered(): void
    {
        foreach (self::REQUIRED as $name) {
            if (config("settings.repositories.{$name}") === null) {
                throw new RuntimeException(self::missingMessage($name));
            }
        }
    }

    private static function missingMessage(string $name): string
    {
        $snippet = <<<'PHP'
'repositories' => [
    // ...
    'site_database' => [
        'type' => \Wsmallnews\Support\Tenant\Settings\Repositories\DatabaseSettingsRepository::class,
        'model' => null,
        'table' => 'sn_site_settings',
        'connection' => null,
    ],
],
PHP;

        return "Missing settings repository [{$name}] in config/settings.php. "
            . "Plugin settings are partitioned per site and per tenant, so spatie's default "
            . "[database] repository cannot serve them. Register the repository:\n\n"
            . "{$snippet}\n";
    }
}
