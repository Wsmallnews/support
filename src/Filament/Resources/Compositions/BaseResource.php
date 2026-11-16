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
use Wsmallnews\Support\Filament\Resources\Concerns\ScopedQueries;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Base resource for compositions. support does not register it: consumers register
 * CompositionResource in their own panel and declare scope_type/scope_id plus module_id,
 * the module whose registered types the form may pick from.
 */
abstract class BaseResource extends Resource
{
    use ScopedQueries;

    protected static ?string $configurationClass = ResourceConfiguration::class;

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
        // module_id is injected by the owning plugin and decides the selectable component types
        return CompositionForm::configure($schema, static::getModuleId());
    }

    public static function table(Table $table): Table
    {
        return CompositionsTable::configure($table, static::getModuleId());
    }

    public static function getEloquentQuery(): Builder
    {
        return static::applyScopeToQuery(parent::getEloquentQuery())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
