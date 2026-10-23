<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Schemas;

use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Livewire\Component;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Features\Composition\CompositionRenderer;
use Wsmallnews\Support\Filament\Forms\FormComponents;

class CompositionForm
{
    public static function configure(Schema $schema, ?string $moduleId = null): Schema
    {
        return $schema
            ->components([
                ...static::forms($moduleId),
            ]);
    }

    /**
     * @param  string|null  $moduleId  Module id, the CompositionRegistry key behind the type and purpose options
     */
    public static function forms(?string $moduleId = null): array
    {
        return [
            Schemas\Components\Section::make(__('sn-support::composition.form.basic_info'))->schema([
                Forms\Components\TextInput::make('title')->label(__('sn-support::composition.form.title'))
                    ->placeholder(__('sn-support::composition.form.title_placeholder'))
                    ->required(),
                FormComponents::orderColumnInput(),
                FormComponents::enumsToggleButtons(CompositionStatus::class),

                // Optional purpose slot: empty means a generic composition that a page can bind; slot
                // compositions are matched by purpose and the first by order_column wins. Modules register
                // the allowed purposes.
                Forms\Components\Select::make('purpose')
                    ->label(__('sn-support::composition.form.purpose'))
                    ->placeholder(__('sn-support::composition.form.purpose_placeholder'))
                    ->options(fn (): array => filled($moduleId) ? CompositionRegistry::getPurposes($moduleId)->toArray() : [])
                    ->helperText(__('sn-support::composition.form.purpose_helper'))
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) use ($moduleId) {
                        // Switching purposes resets the position and syncs the row layout to the new mode
                        $meta = filled($moduleId) && filled($get('purpose'))
                            ? CompositionRegistry::getPurpose($moduleId, $get('purpose'))
                            : null;
                        $position = $meta['default'] ?? null;
                        $set('options.position', $position);
                        static::syncRowsToLayoutMode($moduleId, $get, $set, $get('purpose'), $position);
                    }),

                // Position options and default come from the slot's registered positions (labels may be
                // translation keys or plain text). A slot with a single position hides the field.
                Forms\Components\ToggleButtons::make('options.position')
                    ->label(__('sn-support::composition.form.position'))
                    ->options(function (Get $get) use ($moduleId): array {
                        $meta = filled($moduleId) && filled($get('purpose'))
                            ? CompositionRegistry::getPurpose($moduleId, $get('purpose'))
                            : null;

                        return collect($meta['positions'] ?? [])
                            ->map(fn (string $label): string => __($label))
                            ->all();
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) use ($moduleId) {
                        // Changing position switches layout mode (e.g. top to left), which syncs the row layout
                        static::syncRowsToLayoutMode($moduleId, $get, $set, $get('purpose'), $get('options.position'));
                    })
                    ->visible(function (Get $get) use ($moduleId): bool {
                        $meta = filled($moduleId) && filled($get('purpose'))
                            ? CompositionRegistry::getPurpose($moduleId, $get('purpose'))
                            : null;

                        return count($meta['positions'] ?? []) > 1;
                    })
                    ->inline()
                    ->grouped(),
            ])->columns(2)->columnSpanFull(),

            Schemas\Components\Section::make(__('sn-support::composition.form.layout_section'))
                ->schema([
                    static::rowsRepeater($moduleId),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    /**
     * Row-based composition: each row picks a split from lg up; below lg everything stacks
     * single column, left before right. Stack mode hides the split and forces full width.
     */
    protected static function rowsRepeater(?string $moduleId): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('rows')
            ->label(__('sn-support::composition.form.rows'))
            ->schema(fn () => static::rowSchema($moduleId))
            ->itemLabel(fn (array $state): string => static::layoutOptions()[$state['layout'] ?? null] ?? '')
            ->addActionLabel(fn (Component $livewire): string => static::layoutMode($moduleId, $livewire) === CompositionRenderer::LAYOUT_MODE_STACK
                ? __('sn-support::composition.form.add_sidebar_block')
                : __('sn-support::composition.form.add_row'))
            ->defaultItems(0)               // An empty composition is valid and renders as empty, so do not seed a row
            ->collapsible()
            ->cloneable()
            ->addActionAlignment(Alignment::Start)
            ->columnSpanFull()
            ->statePath('components');
    }

    /**
     * One row: layout picker plus the left and right slot repeaters. The preview widths match
     * the frontend ratio; stack mode hides the split and skips the right slot.
     */
    protected static function rowSchema(?string $moduleId): array
    {
        return [
            Forms\Components\ToggleButtons::make('layout')
                ->label(__('sn-support::composition.form.layout'))
                ->options(static::layoutOptions())
                ->default(CompositionRenderer::LAYOUT_FULL)
                ->helperText(__('sn-support::composition.form.layout_helper'))
                ->inline()
                ->grouped()
                ->live()
                ->required()
                ->visible(fn (Component $livewire): bool => static::layoutMode($moduleId, $livewire) === CompositionRenderer::LAYOUT_MODE_ROWS)
                ->columnSpanFull(),

            Schemas\Components\Grid::make(3)->schema([
                static::slotRepeater('left', __('sn-support::composition.form.slot_left'), $moduleId)
                    ->columnSpan(fn (Get $get) => match ($get('layout')) {
                        CompositionRenderer::LAYOUT_LEFT_NARROW => 1,
                        CompositionRenderer::LAYOUT_LEFT_WIDE => 2,
                        default => 'full',
                    }),

                static::slotRepeater('right', __('sn-support::composition.form.slot_right'), $moduleId)
                    ->columnSpan(fn (Get $get) => match ($get('layout')) {
                        CompositionRenderer::LAYOUT_LEFT_NARROW => 2,
                        CompositionRenderer::LAYOUT_LEFT_WIDE => 1,
                        default => 'full',
                    })
                    // A full-width row never renders the right slot here; stack mode hides it server side too
                    ->visible(fn (Component $livewire): bool => static::layoutMode($moduleId, $livewire) === CompositionRenderer::LAYOUT_MODE_ROWS)
                    ->visibleJs(<<<'JS'
                        $get('layout') != 'full'
                    JS),
            ])->columnSpanFull(),
        ];
    }

    /** Slot repeater; only the left slot is used when the row is full width. */
    protected static function slotRepeater(string $slot, string $label, ?string $moduleId): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($slot)
            ->label($label)
            ->schema(fn () => static::componentItems($moduleId))
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
            ->addActionLabel(__('sn-support::composition.form.add_component'))
            ->defaultItems(0)               // No seeded items: a required type would block saving, and hidden right slots still validate
            ->collapsible()
            ->cloneable();
    }

    /**
     * Component entry: type, label, description and the type's own form fields. Registry form
     * closures need the whole form state, read from the injected Livewire component.
     */
    protected static function componentItems(?string $moduleId): array
    {
        return [
            Forms\Components\Select::make('type')
                ->label(__('sn-support::composition.form.component_type'))
                ->placeholder(__('sn-support::composition.form.component_type_placeholder'))
                ->options(fn (): array => filled($moduleId) ? CompositionRegistry::getTypesOptions($moduleId) : [])
                ->live()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, Component $livewire) use ($moduleId) {
                    // Default content type label
                    $set('label', filled($moduleId) ? (CompositionRegistry::getTypesOptions($moduleId)[$get('type')] ?? '') : '');

                    // Filament's dynamic field pattern (live Select plus schema closure) cannot be used here:
                    // the fields sit under statePath('extras') inside two nested repeaters, so extras is null
                    // when the type changes and entangle cannot write keys onto null. Seed extras explicitly
                    // from the new type's form and drop the previous type's leftovers instead.
                    $fields = filled($moduleId) && filled($get('type'))
                        ? CompositionRegistry::getTypeForms($moduleId, $get('type'), ['fields' => $livewire->data])
                        : [];
                    $extras = [];
                    foreach ($fields as $field) {
                        $extras[$field->getName()] = $field->getDefaultState();
                    }
                    if ($extras !== []) {
                        $set('extras', $extras);
                    }
                }),

            Forms\Components\TextInput::make('label')
                ->label(__('sn-support::composition.form.component_label'))
                ->live(onBlur: true)
                ->placeholder(__('sn-support::composition.form.component_label_placeholder')),

            Forms\Components\TextInput::make('description')
                ->label(__('sn-support::composition.form.component_description'))
                ->live(onBlur: true)
                ->placeholder(__('sn-support::composition.form.component_description_placeholder')),

            // Display options: the header and outer container can be turned off per entry
            Forms\Components\ToggleButtons::make('show_header')
                ->label(__('sn-support::composition.form.show_header'))
                ->boolean()
                ->default(true)
                ->inline()
                ->grouped()
                ->helperText(__('sn-support::composition.form.show_header_helper')),

            Forms\Components\ToggleButtons::make('contained')
                ->label(__('sn-support::composition.form.contained'))
                ->boolean()
                ->default(true)
                ->inline()
                ->grouped()
                ->helperText(__('sn-support::composition.form.contained_helper')),

            Schemas\Components\Fieldset::make('extras')
                ->label(__('sn-support::composition.form.component_options'))
                ->schema(function (Get $get, Component $livewire) use ($moduleId) {
                    return filled($moduleId) && filled($get('type')) ? CompositionRegistry::getTypeForms($moduleId, $get('type'), ['fields' => $livewire->data]) : [];
                })->visible(function (Get $get, Component $livewire) use ($moduleId) {
                    $hasForms = filled($moduleId) && filled($get('type')) ? CompositionRegistry::hasTypeForms($moduleId, $get('type'), ['fields' => $livewire->data]) : false;

                    // A type is selected and it has form fields
                    return filled($moduleId) && filled($get('type')) && $hasForms;
                })
                ->columns(1)          // Single column: slots vary in width and these forms have no container queries
                ->columnSpanFull()
                ->statePath('extras')
                ->key('dynamicExtrasFields'),    // Fixed relative key: an absolute key appends the item uuid, and a random one rebuilds the DOM
        ];
    }

    /**
     * Current editing layout mode (rows or stack), read from the injected component because
     * purpose and options.position live at the root and deep relative paths are unreliable.
     */
    protected static function layoutMode(?string $moduleId, Component $livewire): string
    {
        $data = $livewire->data ?? [];

        return CompositionRegistry::getLayoutMode($moduleId, $data['purpose'] ?? null, $data['options']['position'] ?? null);
    }

    /**
     * Sync all rows to the new mode after a purpose or position change: stack forces full
     * width, while right-slot data stays in the state so switching back loses nothing.
     */
    protected static function syncRowsToLayoutMode(?string $moduleId, Get $get, Set $set, ?string $purpose, ?string $position): void
    {
        if (CompositionRegistry::getLayoutMode($moduleId, $purpose, $position) !== CompositionRenderer::LAYOUT_MODE_STACK) {
            return;
        }

        $rows = collect($get('components') ?? [])
            ->map(fn (array $row): array => [...$row, 'layout' => CompositionRenderer::LAYOUT_FULL])
            ->all();

        $set('components', $rows);
    }

    /** Layout options, mirroring the CompositionRenderer constants. */
    protected static function layoutOptions(): array
    {
        return [
            CompositionRenderer::LAYOUT_FULL => __('sn-support::composition.form.layout_full'),
            CompositionRenderer::LAYOUT_LEFT_NARROW => __('sn-support::composition.form.layout_left_narrow'),
            CompositionRenderer::LAYOUT_LEFT_WIDE => __('sn-support::composition.form.layout_left_wide'),
        ];
    }
}
