<?php

namespace Wsmallnews\Support\Commands;

use Illuminate\Console\Command;
use Throwable;
use Wsmallnews\Support\Enums\ScheduledTaskStatus;
use Wsmallnews\Support\ScheduledTaskRegistry;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class RunScheduledTasksCommand extends Command
{
    protected $signature = 'sn-support:run-scheduled-tasks';

    protected $description = '执行到期的定时调度任务';

    public function handle(ScheduledTaskRegistry $registry): int
    {
        $modelClass = SupportUtils::getScheduledTaskModel();
        $batchSize = SupportUtils::getSchedulerConfig('batch_size', 100);

        $tasks = $modelClass::due()->limit($batchSize)->get();

        if ($tasks->isEmpty()) {
            $this->info('No scheduled tasks due.');

            return self::SUCCESS;
        }

        $executed = 0;
        $failed = 0;

        foreach ($tasks as $task) {
            $handler = $registry->getHandler($task->schedulable_type, $task->action);

            if (! $handler) {
                $task->update([
                    'status' => ScheduledTaskStatus::Failed,
                    'result' => "No handler for {$task->schedulable_type}.{$task->action}",
                ]);
                $failed++;

                continue;
            }

            try {
                $result = $handler($task, $task->payload);

                $task->update([
                    'status' => ScheduledTaskStatus::Executed,
                    'executed_at' => now(),
                    'result' => is_bool($result) ? ($result ? 'success' : 'failed') : (string) $result,
                ]);
                $executed++;
            } catch (Throwable $e) {
                $task->update([
                    'status' => ScheduledTaskStatus::Failed,
                    'result' => $e->getMessage(),
                ]);
                $failed++;

                exception_log($e, 'ScheduledTask');
            }
        }

        $this->info("Executed {$executed} scheduled tasks" . ($failed ? ", {$failed} failed." : '.'));

        return self::SUCCESS;
    }
}
