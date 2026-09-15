<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Wsmallnews\Support\Filament\Resources\Compositions\Schemas\CompositionForm;
use Wsmallnews\Support\Filament\Resources\Compositions\Tables\CompositionsTable;
use Wsmallnews\Support\Filament\Resources\Concerns\Scopeable;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 内容编排资源基类：承载编排资源的全部默认值（模型经 sn-support.models.composition 可替换）。
 *
 * support 自身不注册本资源——消费方（cms、shop 等模块）在自己的 panel_register 配置中注册
 * \Wsmallnews\Support\Filament\Resources\Compositions\CompositionResource 并声明
 * scope_type / scope_id（数据隔离）与 module_id（组件来源模块，供表单选择 CompositionRegistry
 * 注册的组件）。自定义时继承本类并 use CanBeConfigured（getModuleId 经插件自动解析）。
 */
abstract class BaseResource extends Resource
{
    use Scopeable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string | BackedEnum | null $activeNavigationIcon = Heroicon::Squares2x2;

    protected static ?string $slug = 'compositions';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    public static function getModel(): string
    {
        return SupportUtils::getCompositionModel();
    }

    public static function getModelLabel(): string
    {
        return static::$modelLabel ?? __('sn-support::composition.resource.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? __('sn-support::composition.resource.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? __('sn-support::composition.resource.navigation_label');
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        // module_id 由消费方配置声明（或经 CanBeConfigured 插件解析），决定表单可选的组件类型
        return CompositionForm::configure($schema, static::getModuleId());
    }

    public static function table(Table $table): Table
    {
        return CompositionsTable::configure($table, static::getModuleId());
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyScopeableToQuery(parent::getEloquentQuery())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
