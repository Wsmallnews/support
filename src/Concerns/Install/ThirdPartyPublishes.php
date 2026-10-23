<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Concerns\Install;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

trait ThirdPartyPublishes
{
    /**
     * Publish third-party configs and migrations with task-style output.
     *
     * @param  Command  $command  Install command, must support components->task
     * @param  array<int, array{provider: string, tag: string, label: string}>  $publishes
     */
    protected function publishThirdParty(Command $command, array $publishes, bool $force = false): void
    {
        $command->newLine();

        foreach ($publishes as $item) {
            $command->components->task("Publishing {$item['label']}", function () use ($item, $force) {
                $params = [
                    '--provider' => $item['provider'],
                    '--tag' => $item['tag'],
                    '--no-interaction' => true,
                ];

                if ($force) {
                    $params['--force'] = true;
                }

                return Artisan::call('vendor:publish', $params) === Command::SUCCESS;
            });
        }
    }
}
