<?php

namespace Wsmallnews\Support\Helpers;

use Illuminate\Console\Scheduling\Event;

class ScheduleHelper
{
    /**
     * Configure schedule task.
     *
     * $config format:
     * [
     *     'frequency' => 'everyFiveMinutes',
     *     'without_overlapping' => true,
     *     'overlapping_expire_minutes' => 10,
     * ]
     */
    public static function configure(Event $task, array $config): void
    {
        $frequency = $config['frequency'] ?? 'everyMinute';
        static::applyFrequency($task, $frequency);

        if ($config['without_overlapping'] ?? false) {
            $expireMinutes = $config['overlapping_expire_minutes'] ?? null;

            if ($expireMinutes !== null) {
                $task->withoutOverlapping($expireMinutes);
            } else {
                $task->withoutOverlapping();
            }
        }
    }

    /**
     * Parse frequency string and call corresponding schedule method.
     *
     * Supported formats:
     *   "everyMinute"          → $task->everyMinute()
     *   "everyFiveMinutes"     → $task->everyFiveMinutes()
     *   "dailyAt:13:00"        → $task->dailyAt('13:00')
     *   "monthlyOn:4,15:00"    → $task->monthlyOn(4, '15:00')
     *   "twiceDaily:1,13"      → $task->twiceDaily(1, 13)
     */
    protected static function applyFrequency(Event $task, string $frequency): void
    {
        if (str_contains($frequency, ':')) {
            [$method, $argsString] = explode(':', $frequency, 2);
            $args = array_map(
                fn ($arg) => is_numeric($arg) ? (int) $arg : $arg,
                explode(',', $argsString)
            );
            $task->{$method}(...$args);

            return;
        }

        $task->{$frequency}();
    }
}
