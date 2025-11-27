<?php

namespace Wsmallnews\Support\Filament\Resources\Tags\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Wsmallnews\Support\Enums\TagStatus;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...static::forms(),
            ]);
    }


    public static function forms(): array
    {
        return [
            Schemas\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('名称')
                        ->live(onBlur: true)
                        ->formatStateUsing(fn(Model $record): string => $record->getTranslation('name', app()->getLocale()))
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('slug', Str::slug(title: $state, language: app()->getLocale()));
                        }),
                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->unique(ignorable: fn(?Model $record): ?Model => $record)
                        ->required()
                        ->maxLength(255)
                        ->formatStateUsing(fn(Model $record): string => $record->getTranslation('slug', app()->getLocale())),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }
}
