<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs;

use BackedEnum;
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

    protected static ?string $navigationLabel = '日志管理';

    protected static string | UnitEnum | null $navigationGroup = '系统管理';

    protected static ?string $slug = 'activity-logs';

    protected static ?string $recordTitleAttribute = 'log_name';

    protected static ?string $modelLabel = '日志';

    protected static ?string $pluralModelLabel = '日志';

    protected static ?int $navigationSort = 3;

    /**
     * @return class-string<TModel>
     */
    public static function getModel(): string
    {
        return ActivitylogConfig::activityModel();
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
        // resource 只查询 默认 log_name 的日志
        return parent::getEloquentQuery()->with(['causer', 'subject'])->where('log_name', config('activitylog.default_log_name'));
    }
}
