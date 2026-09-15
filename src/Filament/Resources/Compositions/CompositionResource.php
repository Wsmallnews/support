<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions;

use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\Compositions\Pages\CreateComposition;
use Wsmallnews\Support\Filament\Resources\Compositions\Pages\EditComposition;
use Wsmallnews\Support\Filament\Resources\Compositions\Pages\ListCompositions;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class CompositionResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getPages(): array
    {
        return [
            'index' => ListCompositions::route('/'),
            'create' => CreateComposition::route('/create'),
            'edit' => EditComposition::route('/{record}/edit'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $resolveForm = self::resolveCustomProperty('form');

        return $resolveForm instanceof Closure ? $resolveForm($schema, self::class) : parent::form($schema);
    }

    public static function table(Table $table): Table
    {
        $resolveTable = self::resolveCustomProperty('table');

        return $resolveTable instanceof Closure ? $resolveTable($table, self::class) : parent::table($table);
    }
}
