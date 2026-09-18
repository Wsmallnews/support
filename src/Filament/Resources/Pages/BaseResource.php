<?php

namespace Wsmallnews\Support\Filament\Resources\Pages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Wsmallnews\Support\Filament\Resources\Concerns\Scopeable;
use Wsmallnews\Support\Filament\Resources\Pages\Schemas\PageForm;
use Wsmallnews\Support\Filament\Resources\Pages\Tables\PagesTable;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 站点页面资源基类：页面 = slug → 内容绑定（当前绑 composition），是页面体系的唯一事实源。
 *
 * support 自身不注册本资源——消费方（cms、shop 等模块）在自己的 panel_register 配置中注册
 * \Wsmallnews\Support\Filament\Resources\Pages\PageResource 并声明 scope_type / scope_id（数据
 * 隔离）。页面绑定的编排同样按 scope 过滤可选。自定义时继承本类并 use CanBeConfigured。
 */
abstract class BaseResource extends Resource
{
    use Scopeable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::DocumentText;

    protected static ?string $slug = 'pages';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function getModel(): string
    {
        return SupportUtils::getPageModel();
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? __('sn-support::page.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? __('sn-support::page.resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('sn-support::page.resource.navigation_label');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyScopeableToQuery(parent::getEloquentQuery())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
