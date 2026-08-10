<?php

namespace Wsmallnews\Support\Facades;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Wsmallnews\Support\ScheduledTaskRegistry;

/**
 * @method static static register(string $morphType, array $actionInfo)
 * @method static static registers(string $morphType, array $actionInfos)
 * @method static Collection getActions(string $morphType)
 * @method static array getActionsOptions(string $morphType)
 * @method static array getActionForms(string $morphType, string $action)
 * @method static Closure|null getHandler(string $morphType, string $action)
 * @method static Closure|null getActionVisible(string $morphType, string $action)
 * @method static \Filament\Forms\Components\Repeater scheduleRepeater(string $morphType, string $relationship = 'scheduledTasks')
 *
 * @see \Wsmallnews\Support\ScheduledTaskRegistry
 */
class ScheduledTask extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ScheduledTaskRegistry::class;
    }
}
