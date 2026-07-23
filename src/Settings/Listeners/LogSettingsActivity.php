<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Settings\Listeners;

use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Wsmallnews\Support\Enums\ActivityLogEvent;

class LogSettingsActivity
{
    public function handle(SavingSettings $event): void
    {
        $oldValues = $event->originalValues->toArray();
        $newValues = $event->properties->toArray();

        $changedOld = [];
        $changedNew = [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changedOld[$key] = $oldValue;
                $changedNew[$key] = $newValue;
            }
        }

        if (empty($changedNew)) {
            return;
        }

        activity()
            ->event(ActivityLogEvent::Updated->value)
            ->performedOn((new SettingsProperty))
            ->withChanges([
                'old' => $changedOld,
                'attributes' => $changedNew,
            ])
            ->log(ActivityLogEvent::Updated->getLabel() . ": settings {$event->settings::group()}");
    }
}
