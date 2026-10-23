<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Schemas;

use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Filament\Forms\FormComponents;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class PageForm
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
            Schemas\Components\Section::make(__('sn-support::page.form.basic_info'))->schema([
                Forms\Components\TextInput::make('title')->label(__('sn-support::page.form.title'))
                    ->required()
                    ->maxLength(60),

                Forms\Components\TextInput::make('slug')->label(__('sn-support::page.form.slug'))
                    ->required()
                    ->maxLength(100)
                    ->scopedUnique(modifyQueryUsing: function (Builder $query, Component $livewire): Builder {
                        return $query->scopeable($livewire::getScopeType(), $livewire::getScopeId());
                    })
                    ->helperText(__('sn-support::page.form.slug_helper')),

                FormComponents::orderColumnInput(),
                FormComponents::enumsToggleButtons(PageStatus::class),

                // Optional advanced mode: the frontend renders the bound composition instead of the page
                // content, and the two are mutually exclusive. Only generic compositions are listed.
                Forms\Components\Select::make('composition_id')->label(__('sn-support::page.form.composition'))
                    ->options(function (Component $livewire): array {
                        // Create and Edit pages both use Pages\Scopeable, so the scope is readable directly
                        $scopeable = $livewire->getScopeable();

                        return SupportUtils::getCompositionModel()::query()
                            ->where('scope_type', $scopeable['scope_type'] ?? null)
                            ->where('scope_id', $scopeable['scope_id'] ?? 0)
                            ->whereNull('purpose')
                            ->orderBy('order_column')
                            ->pluck('title', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText(__('sn-support::page.form.composition_helper')),

                // Page content: editable while nothing is bound, hidden once a composition is bound
                FormComponents::contentTypeGroup(required: false)
                    ->visible(fn (Get $get): bool => blank($get('composition_id')))
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ];
    }
}
