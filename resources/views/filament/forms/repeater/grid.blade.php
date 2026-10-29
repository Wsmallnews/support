@php
    use Illuminate\Support\Js;
    use Filament\Forms\Components\Hidden;

    // Mirrors Repeater::toTableEmbeddedHtml() with the same mechanisms
    // (x-sortable, native actions, column settings) — only the layout skeleton
    // changes: table rows become grid rows, column caps carry data-label so
    // narrow-container degradation is pure CSS (no JS stamper).
    $items = $field->getItems();

    $addAction = $field->getAction($field->getAddActionName());
    $addActionAlignment = $field->getAddActionAlignment();
    $cloneAction = $field->getAction($field->getCloneActionName());
    $deleteAction = $field->getAction($field->getDeleteActionName());
    $moveDownAction = $field->getAction($field->getMoveDownActionName());
    $moveUpAction = $field->getAction($field->getMoveUpActionName());
    $reorderAction = $field->getAction($field->getReorderActionName());
    $extraItemActions = $field->getExtraItemActions();

    $isAddable = $field->isAddable();
    $isCloneable = $field->isCloneable();
    $isDeletable = $field->isDeletable();
    $isReorderableWithButtons = $field->isReorderableWithButtons();
    $isReorderableWithDragAndDrop = $field->isReorderableWithDragAndDrop();

    $key = $field->getKey();
    $tableColumns = $field->getTableColumns() ?? [];
    $itemCount = count($items);
    $itemIndex = 0;
    $hasReorderColumn = ($itemCount > 1) && ($isReorderableWithButtons || $isReorderableWithDragAndDrop);
    $hasActionsColumn = count($extraItemActions) || $isCloneable || $isDeletable;

    $columnCount = ($hasReorderColumn ? 1 : 0) + count($tableColumns) + ($hasActionsColumn ? 1 : 0);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" label-tag="div">
    <div
        id="{{ $field->getId() }}"
        role="group"
        aria-labelledby="{{ $field->getId() }}-label"
        {{ $attributes->merge(['class' => 'sn-filament-grid-repeater']) }}
    >
        @if ($itemCount)
            {{-- Column caps: label + width from the native table column definitions. --}}
            <div class="sn-filament-grid-repeater-head" aria-hidden="true">
                @if ($hasReorderColumn)
                    <span class="sn-cell sn-cell-handle"></span>
                @endif

                @foreach ($tableColumns as $column)
                    @php
                        $columnAlignment = $column->getAlignment();
                        $columnWidth = $column->getWidth();
                        $capAttributes = (new \Filament\Support\View\ComponentAttributeBag)->class([
                            'sn-cell',
                            ($columnAlignment instanceof \Filament\Support\Enums\Alignment) ? ('fi-align-' . $columnAlignment->value) : (is_string($columnAlignment) ? $columnAlignment : ''),
                            'sn-cell-num' => $columnAlignment === \Filament\Support\Enums\Alignment::Right,
                        ]);
                        if (filled($columnWidth)) {
                            $capAttributes = $capAttributes->style(['width: ' . e($columnWidth)]);
                        }
                    @endphp
                    <span {!! $capAttributes->toHtml() !!}>
                        @if (! $column->isHeaderLabelHidden())
                            {{ $column->getLabel() }}
                            @if ($column->isMarkedAsRequired())<sup class="sn-cap-required">*</sup>@endif
                        @endif
                    </span>
                @endforeach

                @if ($hasActionsColumn)
                    <span class="sn-cell sn-cell-actions"></span>
                @endif
            </div>

            <ul
                x-sortable
                wire:ignore.self
                data-sortable-animation-duration="{{ $field->getReorderAnimationDuration() }}"
                x-on:end.stop="$wire.mountAction('reorder', { items: $event.target.sortable.toArray() }, { schemaComponent: '{{ $key }}' })"
                class="sn-filament-grid-repeater-items"
            >
                @foreach ($items as $itemKey => $item)
                    @php
                        $itemIndex++;
                        $isFirst = $itemIndex === 1;
                        $isLast = $itemIndex === $itemCount;

                        $visibleExtraItemActions = array_filter(
                            $extraItemActions,
                            fn ($action): bool => $action(['item' => $itemKey])->isVisible(),
                        );
                        $itemCloneAction = $cloneAction(['item' => $itemKey]);
                        $cloneActionIsVisible = $isCloneable && $itemCloneAction->isVisible();
                        $itemDeleteAction = $deleteAction(['item' => $itemKey]);
                        $deleteActionIsVisible = $isDeletable && $itemDeleteAction->isVisible();
                        $itemMoveDownAction = $moveDownAction(['item' => $itemKey])->disabled($isLast);
                        $moveDownActionIsVisible = $isReorderableWithButtons && $itemMoveDownAction->isVisible();
                        $itemMoveUpAction = $moveUpAction(['item' => $itemKey])->disabled($isFirst);
                        $moveUpActionIsVisible = $isReorderableWithButtons && $itemMoveUpAction->isVisible();
                        $reorderActionIsVisible = $isReorderableWithDragAndDrop && $reorderAction->isVisible();
                    @endphp

                    <li
                        wire:key="{{ $item->getLivewireKey() }}.item"
                        x-sortable-item="{{ $itemKey }}"
                        class="sn-filament-grid-repeater-item"
                        style="--sn-cells: {{ $columnCount }}"
                    >
                        @if ($hasReorderColumn && ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible))
                            <div class="sn-cell sn-cell-handle sn-cell-utility">
                                @if ($reorderActionIsVisible)
                                    <div x-on:click.stop>
                                        {!! $reorderAction->extraAttributes(['x-sortable-handle' => true], merge: true)->toHtml() !!}
                                    </div>
                                @endif

                                @if ($moveUpActionIsVisible || $moveDownActionIsVisible)
                                    <div x-on:click.stop>{!! $itemMoveUpAction->toHtml() !!}</div>
                                    <div x-on:click.stop>{!! $itemMoveDownAction->toHtml() !!}</div>
                                @endif
                            </div>
                        @endif

                        @php
                            // Native mapping: visible components line up with table
                            // columns in order; Hidden components render raw (no cell).
                            $counter = 0;
                        @endphp

                        @foreach ($item->getComponents(withHidden: true) as $schemaComponent)
                            @if (count($tableColumns) > $counter)
                                @if ($schemaComponent instanceof Hidden)
                                    {!! $schemaComponent->toHtml() !!}
                                @else
                                    @php
                                        $counter++;
                                        $currentColumn = $tableColumns[$counter - 1] ?? null;
                                        $columnWidth = $currentColumn?->getWidth();
                                        $cellAttributes = (new \Filament\Support\View\ComponentAttributeBag)->class([
                                            'sn-cell',
                                            'sn-cell-first' => $counter === 1,
                                        ]);
                                        if (filled($columnWidth)) {
                                            $cellAttributes = $cellAttributes->style(['width: ' . e($columnWidth)]);
                                        }
                                    @endphp

                                    @if ($schemaComponent->isVisible())
                                        <div
                                            {!! $cellAttributes->toHtml() !!}
                                            data-label="{{ $currentColumn?->getLabel() }}"
                                        >
                                            {!! $schemaComponent->toSchemaHtml() !!}
                                        </div>
                                    @else
                                        <div class="sn-cell fi-hidden" aria-hidden="true"></div>
                                    @endif
                                @endif
                            @endif
                        @endforeach

                        @if ($hasActionsColumn && ($visibleExtraItemActions || $cloneActionIsVisible || $deleteActionIsVisible))
                            <div class="sn-cell sn-cell-actions sn-cell-utility">
                                @foreach ($visibleExtraItemActions as $extraItemAction)
                                    <div x-on:click.stop>{!! $extraItemAction(['item' => $itemKey])->toHtml() !!}</div>
                                @endforeach

                                @if ($cloneActionIsVisible)
                                    <div x-on:click.stop>{!! $itemCloneAction->toHtml() !!}</div>
                                @endif

                                @if ($deleteActionIsVisible)
                                    <div x-on:click.stop>{!! $itemDeleteAction->toHtml() !!}</div>
                                @endif
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($isAddable && $addAction->isVisible())
            <div class="{{ \Illuminate\Support\Arr::toCssClasses([
                'sn-filament-grid-repeater-add',
                ($addActionAlignment instanceof \Filament\Support\Enums\Alignment) ? ('fi-align-' . $addActionAlignment->value) : $addActionAlignment,
            ]) }}">
                {!! $addAction->toHtml() !!}
            </div>
        @endif
    </div>
</x-dynamic-component>
