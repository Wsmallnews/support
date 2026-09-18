<?php

namespace Wsmallnews\Support\Filament\Resources\Pages;

use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\Pages\Pages\CreatePage;
use Wsmallnews\Support\Filament\Resources\Pages\Pages\EditPage;
use Wsmallnews\Support\Filament\Resources\Pages\Pages\ListPages;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class PageResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
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
