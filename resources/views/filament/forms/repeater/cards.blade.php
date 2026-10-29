@php
    use Illuminate\Support\Js;
    use Filament\Forms\Components\Hidden;

    // Mirrors Repeater::toEmbeddedHtml() (card mode) with the same mechanisms
    // (x-sortable, collapse persistence, native actions). One extension: when
    // viewData carries a headerField, that named schema field is rendered inline
    // in the item header (next to handle / collapse / delete) and skipped in the
    // body — the "name the group" pattern for spec items and media groups.
    $items = $field->getItems();

    $addAction = $field->getAction($field->getAddActionName());
    $addActionAlignment = $field->getAddActionAlignment();
    $cloneAction = $field->getAction($field->getCloneActionName());
    $collapseAllAction = $field->getAction($field->getCollapseAllActionName());
    $expandAllAction = $field->getAction($field->getExpandAllActionName());
    $deleteAction = $field->getAction($field->getDeleteActionName());
    $moveDownAction = $field->getAction($field->getMoveDownActionName());
    $moveUpAction = $field->getAction($field->getMoveUpActionName());
    $reorderAction = $field->getAction($field->getReorderActionName());
    $extraItemActions = $field->getExtraItemActions();

    $hasItemNumbers = $field->hasItemNumbers();
    $isAddable = $field->isAddable();
    $isCloneable = $field->isCloneable();
    $isCollapsible = $field->isCollapsible();
    $isDeletable = $field->isDeletable();
    $isReorderableWithButtons = $field->isReorderableWithButtons();
    $isReorderableWithDragAndDrop = $field->isReorderableWithDragAndDrop();
    $persistCollapsed = $field->shouldPersistCollapsed();

    $key = $field->getKey();
    $statePath = $field->getStatePath();
    $itemCount = count($items);
    $itemIndex = 0;

    $headerField = $headerField ?? null;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" label-tag="div">
    <div
        id="{{ $field->getId() }}"
        role="group"
        aria-labelledby="{{ $field->getId() }}-label"
        {{ $attributes->merge(['class' => \Illuminate\Support\Arr::toCssClasses([
            'fi-fo-repeater',
            'sn-filament-cards-repeater',
            'fi-collapsible' => $isCollapsible,
        ])]) }}
    >
        @php
            $collapseAllActionIsVisible = $isCollapsible && $collapseAllAction->isVisible();
            $expandAllActionIsVisible = $isCollapsible && $expandAllAction->isVisible();
        @endphp

        @if ($collapseAllActionIsVisible || $expandAllActionIsVisible)
            <div class="{{ \Illuminate\Support\Arr::toCssClasses(['fi-fo-repeater-actions', 'fi-hidden' => $itemCount < 2]) }}">
                @if ($collapseAllActionIsVisible)
                    <span x-on:click="$dispatch('repeater-collapse', '{{ $statePath }}')">{!! $collapseAllAction->toHtml() !!}</span>
                @endif

                @if ($expandAllActionIsVisible)
                    <span x-on:click="$dispatch('repeater-expand', '{{ $statePath }}')">{!! $expandAllAction->toHtml() !!}</span>
                @endif
            </div>
        @endif

        @if ($itemCount)
            <ul
                x-sortable
                wire:ignore.self
                {!! (new \Filament\Support\View\ComponentAttributeBag)->grid($field->getGridColumns())->class(['fi-fo-repeater-items', 'sn-filament-cards-repeater-items'])->merge(['data-sortable-animation-duration' => $field->getReorderAnimationDuration()], escape: false)->toHtml() !!}
                x-on:end.stop="$wire.mountAction('reorder', { items: $event.target.sortable.toArray() }, { schemaComponent: '{{ $key }}' })"
            >
                @foreach ($items as $itemKey => $item)
                    @php
                        $itemIndex++;
                        $isFirst = $itemIndex === 1;
                        $isLast = $itemIndex === $itemCount;

                        $itemLabel = $field->getItemLabel($itemKey, $itemIndex - 1);
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

                        // ${...} template literals clash with Blade's {{ }} — build the
                        // collapse expression in PHP and emit it raw once.
                        $collapsedExpression = $persistCollapsed
                            ? '$persist(' . Js::from($field->isCollapsed($item)) . ').as(`repeater-${' . Js::from($key) . '}-${' . Js::from($itemKey) . '}-isCollapsed`)'
                            : (string) Js::from($field->isCollapsed($item));
                    @endphp

                    <li
                        wire:key="{{ $item->getLivewireKey() }}.item"
                        x-data="{ isCollapsed: {!! $collapsedExpression !!} }"
                        x-on:repeater-expand.window="$event.detail === '{{ $statePath }}' && (isCollapsed = false)"
                        x-on:repeater-collapse.window="$event.detail === '{{ $statePath }}' && (isCollapsed = true)"
                        x-on:expand="isCollapsed = false"
                        x-sortable-item="{{ $itemKey }}"
                        class="fi-fo-repeater-item fi-fo-repeater-item-has-header sn-filament-cards-repeater-item"
                        x-bind:class="{ 'fi-collapsed': isCollapsed }"
                    >
                        <div
                            @if ($isCollapsible)
                                x-on:click.stop="isCollapsed = !isCollapsed"
                            @endif
                            class="fi-fo-repeater-item-header sn-filament-cards-repeater-item-header"
                        >
                            @if ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible)
                                <ul class="fi-fo-repeater-item-header-start-actions">
                                    @if ($reorderActionIsVisible)
                                        <li x-on:click.stop>
                                            {!! $reorderAction->extraAttributes(['x-sortable-handle' => true], merge: true)->toHtml() !!}
                                        </li>
                                    @endif

                                    @if ($moveUpActionIsVisible || $moveDownActionIsVisible)
                                        <li x-on:click.stop>{!! $itemMoveUpAction->toHtml() !!}</li>
                                        <li x-on:click.stop>{!! $itemMoveDownAction->toHtml() !!}</li>
                                    @endif
                                </ul>
                            @endif

                            @if ($hasItemNumbers)
                                <span class="sn-card-index">{{ $itemIndex }}</span>
                            @endif

                            {{-- headerField: the named field renders inline here (label / helper
                                 stripped by CSS in this context); it is skipped in the body below. --}}
                            @if (filled($headerField))
                                @foreach ($item->getComponents(withHidden: true) as $schemaComponent)
                                    @if (is_a($schemaComponent, \Filament\Schemas\Components\Component::class) && method_exists($schemaComponent, 'getName') && $schemaComponent->getName() === $headerField && $schemaComponent->isVisible())
                                        <div class="sn-card-header-field" x-on:click.stop>
                                            {!! $schemaComponent->toSchemaHtml() !!}
                                        </div>
                                    @endif
                                @endforeach
                            @endif

                            @if (filled($itemLabel))
                                <span class="fi-fo-repeater-item-header-label sn-card-label">{{ $itemLabel }}</span>
                            @endif

                            @if ($cloneActionIsVisible || $deleteActionIsVisible || $isCollapsible || $visibleExtraItemActions)
                                <ul class="fi-fo-repeater-item-header-end-actions">
                                    @foreach ($visibleExtraItemActions as $extraItemAction)
                                        <li x-on:click.stop>{!! $extraItemAction(['item' => $itemKey])->toHtml() !!}</li>
                                    @endforeach

                                    @if ($cloneActionIsVisible)
                                        <li x-on:click.stop>{!! $itemCloneAction->toHtml() !!}</li>
                                    @endif

                                    @if ($deleteActionIsVisible)
                                        <li x-on:click.stop>{!! $itemDeleteAction->toHtml() !!}</li>
                                    @endif

                                    @if ($isCollapsible)
                                        <li class="fi-fo-repeater-item-header-collapsible-actions" x-on:click.stop="isCollapsed = !isCollapsed">
                                            <div class="fi-fo-repeater-item-header-collapse-action">
                                                {!! $field->getAction('collapse')->toHtml() !!}
                                            </div>
                                            <div class="fi-fo-repeater-item-header-expand-action">
                                                {!! $field->getAction('expand')->toHtml() !!}
                                            </div>
                                        </li>
                                    @endif
                                </ul>
                            @endif
                        </div>

                        <div x-show="! isCollapsed" class="fi-fo-repeater-item-content sn-filament-cards-repeater-item-content">
                            {{-- Body grid mirrors the vendor item schema container (Schema::render
                                 calls ->grid($schema->getColumns())): the repeater's ->columns()/
                                 columnSpan stay effective here, and every component keeps its
                                 wrapper (fi-hidden when invisible) for stable Livewire DOM diffing.
                                 Hidden components render raw (no cell), the headerField is skipped —
                                 it lives in the header. --}}
                            <div {!! (new \Filament\Support\View\ComponentAttributeBag)->grid($item->getColumns())->toHtml() !!}>
                                @foreach ($item->getComponents(withHidden: true) as $schemaComponent)
                                    @if ($schemaComponent instanceof Hidden)
                                        {!! $schemaComponent->toHtml() !!}
                                    @elseif (filled($headerField) && method_exists($schemaComponent, 'getName') && $schemaComponent->getName() === $headerField)
                                        @continue
                                    @else
                                        {!! $schemaComponent->toSchemaHtml($schemaComponent->isVisible()) !!}
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($isAddable && $addAction->isVisible())
            <div class="{{ \Illuminate\Support\Arr::toCssClasses([
                'fi-fo-repeater-add',
                'sn-filament-cards-repeater-add',
                ($addActionAlignment instanceof \Filament\Support\Enums\Alignment) ? ('fi-align-' . $addActionAlignment->value) : $addActionAlignment,
            ]) }}">
                {!! $addAction->toHtml() !!}
            </div>
        @endif
    </div>
</x-dynamic-component>
