<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Support\Config as ActivitylogConfig;
use UnitEnum;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
use Wsmallnews\Support\Filament\Resources\ActivityLogs\Tables\ActivityLogTable;

abstract class BaseResource extends Resource
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::DocumentText;

    protected static ?string $slug = 'activity-logs';

    protected static ?string $recordTitleAttribute = 'log_name';

    protected static ?int $navigationSort = 3;

    /**
     * @return class-string<TModel>
     */
    public static function getModel(): string
    {
        return ActivitylogConfig::activityModel();
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? __('sn-support::activity.activity_log_resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? __('sn-support::activity.activity_log_resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('sn-support::activity.activity_log_resource.navigation_label');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::$navigationGroup ?? __('sn-support::activity.activity_log_resource.navigation_group');
    }

    public static function table(Table $table): Table
    {
        return ActivityLogTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityLogInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $panel = Filament::getCurrentPanel();

        // resource 只查询 默认 log_name 的日志
        return parent::getEloquentQuery()->with([
            'causer' => fn ($query) => $query->withoutGlobalScope($panel->getTenancyScopeName()),       // 查询的有普通用户日志，不能限制只关联管理员（移除全局作用域）
            'subject',
        ])->where('log_name', config('activitylog.default_log_name'));
    }
}
