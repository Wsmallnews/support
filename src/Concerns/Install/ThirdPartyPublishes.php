<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Concerns\Install;

use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Support\Facades\Artisan;

trait ThirdPartyPublishes
{
    protected function publishThirdParty(ConsoleCommand $command, array $publishes, bool $force = false): void
    {
        $command->comment('Third-party dependencies (owned by support):');

        foreach ($publishes as $item) {
            $params = [
                '--provider' => $item['provider'],
                '--tag' => $item['tag'],
                '--ansi' => true,
            ];

            if ($force) {
                $params['--force'] = true;
            }

            Artisan::call('vendor:publish', $params, $command->getOutput());
            $command->comment("  Published: {$item['label']}");
        }

        $command->newLine();
    }
}
