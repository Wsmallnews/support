<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Schemas;

use Closure;
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
                        return $query->forScopeable($livewire::getScopeable());
                    })
                    ->helperText(__('sn-support::page.form.slug_helper')),

                FormComponents::orderColumnInput(),
                FormComponents::enumsToggleButtons(PageStatus::class),

                // Optional advanced mode: the frontend renders the bound composition instead of the page
                // content, and the two are mutually exclusive. Only generic compositions are listed.
                Forms\Components\Select::make('composition_id')->label(__('sn-support::page.form.composition'))
                    ->options(function (Component $livewire): array {
                        // Compositions live in their own data space: a resource maps
                        // the 'composition' key to a sub-scope, otherwise the site's
                        // base scope applies.
                        return SupportUtils::getCompositionModel()::query()
                            ->published()
                            ->forScopeable($livewire::getRelatedScopeable('composition'))
                            ->whereNull('purpose')
                            ->orderBy('order_column')
                            ->pluck('title', 'id')
                            ->all();
                    })
                    // The bound value may be a draft or hidden composition: resolve its
                    // label separately so editing never falls back to a raw id.
                    ->getOptionLabelUsing(fn ($value): ?string => SupportUtils::getCompositionModel()::query()
                        ->withTrashed()
                        ->whereKey($value)
                        ->value('title'))
                    ->rules(fn (Component $livewire): Closure => function (string $attribute, mixed $value, Closure $fail) use ($livewire): void {
                        if (blank($value)) {
                            return;
                        }

                        // Reading trusts the binding, so the data space is enforced here.
                        $exists = SupportUtils::getCompositionModel()::query()
                            ->whereKey($value)
                            ->whereNull('purpose')
                            ->forScopeable($livewire::getRelatedScopeable('composition'))
                            ->exists();

                        if (! $exists) {
                            $fail(__('sn-support::page.form.composition_invalid'));
                        }
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
