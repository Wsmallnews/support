<?php

namespace Wsmallnews\Support\Filament\Resources\Tags;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Wsmallnews\Support\Filament\Resources\Tags\Schemas\TagForm;
use Wsmallnews\Support\Filament\Resources\Tags\Tables\TagsTable;
use Wsmallnews\Support\Filament\Resources\Concerns\Scopeable;
use Wsmallnews\Support\Models\Tag;

abstract class BaseResource extends Resource
{
    use Scopeable;

    protected static ?string $model = Tag::class;
    
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static string | BackedEnum | null $activeNavigationIcon = 'heroicon-s-tag';

    protected static ?string $navigationLabel = '标签管理';

    protected static string | UnitEnum | null $navigationGroup = '内容管理';

    protected static ?string $slug = 'tags';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = '标签';

    protected static ?string $pluralModelLabel = '标签';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
    }

    public static function getTagType(): string
    {
        return 'default';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->scopeable(static::getScopeType(), static::getScopeId());
    }
}
