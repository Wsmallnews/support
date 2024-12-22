@php
    use Filament\Forms\Components\Actions\Action;

    $containers = $getChildComponentContainers();

    $addAction = $getAction($getAddActionName());
    $cloneAction = $getAction($getCloneActionName());
    $deleteAction = $getAction($getDeleteActionName());
    $moveDownAction = $getAction($getMoveDownActionName());
    $moveUpAction = $getAction($getMoveUpActionName());
    $reorderAction = $getAction($getReorderActionName());
    $isReorderableWithButtons = $isReorderableWithButtons();
    $extraItemActions = $getExtraItemActions();

    $headers = $getHeaders();
    $columnWidths = $getColumnWidths();
    $breakPoint = $getBreakPoint();
    $hasContainers = count($containers) > 0;
    $hasHiddenHeader = $shouldHideHeader();
    $statePath = $getStatePath();

    $emptyLabel = $getEmptyLabel();

    $visibleExtraItemActions = [];

    foreach ($containers as $uuid => $row) {
        $visibleExtraItemActions = array_filter(
            $extraItemActions,
            fn (Action $action): bool => $action(['item' => $uuid])->isVisible(),
        );
    }

    $hasActions = $reorderAction->isVisible()
        || $cloneAction->isVisible()
        || $deleteAction->isVisible()
        || $moveUpAction->isVisible()
        || $moveDownAction->isVisible()
        || filled($visibleExtraItemActions);
@endphp

@if (count($containers))
    @foreach ($containers as $uuid => $row)
        @php
            $hasTableRepeater = false;
        @endphp

        <tr
            wire:key="{{ $this->getId() }}.{{ $row->getStatePath() }}.{{ $field::class }}.item"
            x-sortable-item="{{ $uuid }}"
            class="filament-table-repeater-row md:divide-x md:divide-gray-950/5 dark:md:divide-white/20"
        >
            @foreach($row->getComponents() as $cell)
                @if($cell instanceof \Wsmallnews\Support\Forms\Fields\TableRepeater)
                    @php
                        $hasTableRepeater = true;
                    @endphp
                    @continue;
                @endif

                @if(! $cell instanceof \Filament\Forms\Components\Hidden && ! $cell->isHidden())
                    <td
                        @class([
                            'filament-table-repeater-column p-2 first:pl-12',
                            'has-hidden-label' => $cell->isLabelHidden(),
                        ])
                        @php
                            $cellKey = method_exists($cell, 'getName') ? $cell->getName() : $cell->getId();
                        @endphp
                        @if (
                            $columnWidths &&
                            isset($columnWidths[$cellKey])
                        )
                            style="width: {{ $columnWidths[$cellKey] }}"
                        @endif
                    >
                        {{ $cell }}
                    </td>
                @else
                    {{ $cell }}
                @endif
            @endforeach

            @if ($hasActions)
                <td class="filament-table-repeater-column p-2 w-px">
                    <ul class="flex items-center gap-x-3 lg:justify-center">
                        @foreach ($visibleExtraItemActions as $extraItemAction)
                            <li>
                                {{ $extraItemAction(['item' => $uuid]) }}
                            </li>
                        @endforeach

                        @if ($reorderAction->isVisible())
                            <li x-sortable-handle class="shrink-0">
                                {{ $reorderAction }}
                            </li>
                        @endif

                        @if ($isReorderableWithButtons)
                            @if (! $loop->first)
                                <li>
                                {{ $moveUpAction(['item' => $uuid]) }}
                                </li>
                            @endif

                            @if (! $loop->last)
                                <li>
                                {{ $moveDownAction(['item' => $uuid]) }}
                                </li>
                            @endif
                        @endif

                        @if ($cloneAction->isVisible())
                            <li>
                            {{ $cloneAction(['item' => $uuid]) }}
                            </li>
                        @endif

                        @if ($deleteAction->isVisible())
                            <li>
                            {{ $deleteAction(['item' => $uuid]) }}
                            </li>
                        @endif
                    </ul>
                </td>
            @endif
        </tr>

        @if ($hasTableRepeater)
            @foreach($row->getComponents() as $cell)
                @if(!$cell instanceof \Wsmallnews\Support\Forms\Fields\TableRepeater)
                    @continue;
                @endif

                @if($cell->getIsFusionLayout())
                    {{ $cell }}
                @else
                    <tr
                        wire:key="{{ $this->getId() }}.{{ $row->getStatePath() }}.{{ $field::class }}.{{ $cell->getName() }}.table-repeater-item"
                        x-sortable-item="{{ $uuid }}"
                        class="filament-table-repeater-row md:divide-x md:divide-gray-950/5 dark:md:divide-white/20"
                    >
                        <td
                            @class([
                                'filament-table-repeater-column p-2 pl-12',
                                'has-hidden-label' => $cell->isLabelHidden(),
                            ])
                            colspan="{{ count($headers) + ($hasActions ? 1 : 0) }}"
                        >
                            {{ $cell }}
                        </td>
                    </tr>
                @endif
            @endforeach
        @endif
    @endforeach
@else
    <tr class="filament-table-repeater-row filament-table-repeater-empty-row md:divide-x md:divide-gray-950/5 dark:md:divide-divide-white/20">
        <td colspan="{{ count($headers) + intval($hasActions) }}" class="filament-table-repeater-column filament-table-repeater-empty-column p-4 w-px text-center italic">
            {{ $emptyLabel ?: __('filament-table-repeater::components.repeater.empty.label') }}
        </td>
    </tr>
@endif


{{-- @if ($addAction->isVisible())
    <div class="relative flex justify-center">
        {{ $addAction }}
    </div>
@endif --}}
