<?php

namespace Wsmallnews\Support\Filament\Resources\ScheduledTasks;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Schemas\ScheduledTaskInfolist;
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Tables\ScheduledTaskTable;
use Wsmallnews\Support\Support\Utils as SupportUtils;

abstract class BaseResource extends Resource
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClock;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::Clock;

    protected static ?string $slug = 'scheduled-tasks';

    protected static ?string $recordTitleAttribute = 'action';

    protected static ?int $navigationSort = 3;

    /**
     * @return class-string<TModel>
     */
    public static function getModel(): string
    {
        return SupportUtils::getScheduledTaskModel();
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? __('sn-support::scheduled_task.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? __('sn-support::scheduled_task.resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('sn-support::scheduled_task.resource.navigation_label');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::$navigationGroup ?? __('sn-support::scheduled_task.resource.navigation_group');
    }

    public static function table(Table $table): Table
    {
        return ScheduledTaskTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScheduledTaskInfolist::configure($schema);
    }
}
