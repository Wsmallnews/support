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
    $columnCsses = $getColumnCsses();

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

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{}"
        {{ $attributes->merge($getExtraAttributes())->class([
            'filament-box-repeater-component space-y-6 relative',
            match ($breakPoint) {
                'sm' => 'break-point-sm',
                'lg' => 'break-point-lg',
                'xl' => 'break-point-xl',
                '2xl' => 'break-point-2xl',
                default => 'break-point-md',
            }
        ]) }}
    >
        @if (count($containers) || $emptyLabel !== false)
            <div @class([
                'filament-box-repeater-container rounded-xl relative ring-1 ring-gray-950/5 dark:ring-white/20',
                'sm:ring-gray-950/5 dark:sm:ring-white/20' => ! $hasContainers && $breakPoint === 'sm',
                'md:ring-gray-950/5 dark:md:ring-white/20' => ! $hasContainers && $breakPoint === 'md',
                'lg:ring-gray-950/5 dark:lg:ring-white/20' => ! $hasContainers && $breakPoint === 'lg',
                'xl:ring-gray-950/5 dark:xl:ring-white/20' => ! $hasContainers && $breakPoint === 'xl',
                '2xl:ring-gray-950/5 dark:2xl:ring-white/20' => ! $hasContainers && $breakPoint === '2xl',
            ])>

                <div @class([
                    'flex justify-end items-center min-h-12 overflow-hidden',
                    'bg-gray-200 dark:bg-gray-900/60 text-gray-700 dark:text-gray-200',
                    'sr-only' => $hasHiddenHeader
                ]) >
                    @foreach ($headers as $key => $header)
                        <div
                            @class([
                                'px-3 py-2 font-medium',
                                'ltr:rounded-tl-xl rtl:rounded-tr-xl' => $loop->first,
                                'ltr:rounded-tr-xl rtl:rounded-tl-xl' => $loop->last && ! $hasActions,
                                $columnCsses[$key] ?? '',
                                match($getHeadersAlignment()) {
                                    'center' => 'text-center',
                                    'right' => 'text-right rtl:text-left',
                                    default => 'text-left rtl:text-right'
                                }
                            ])
                            @if ($header['width'])
                                style="width: {{ $header['width'] }}"
                            @endif
                        >
                            {{ $header['label'] }}
                            @if ($header['required'])
                                <span class="whitespace-nowrap">
                                    <sup class="font-medium text-danger-700 dark:text-danger-400">*</sup>
                                </span>
                            @endif
                        </div>
                    @endforeach
                    @if ($hasActions)
                        <div class="text-right px-3 py-2 font-medium">
                            <div class="flex items-center gap-2 md:justify-center">
                                @foreach ($visibleExtraItemActions as $extraItemAction)
                                    <div class="w-8"></div>
                                @endforeach

                                @if ($reorderAction->isVisible())
                                    <div class="w-8"></div>
                                @endif

                                @if ($isReorderableWithButtons)
                                    @if ($moveUpAction && count($containers) > 2)
                                        <div class="w-8"></div>
                                    @endif

                                    @if ($moveDownAction && count($containers) > 2)
                                        <div class="w-8"></div>
                                    @endif
                                @endif

                                @if ($cloneAction->isVisible())
                                    <div class="w-8"></div>
                                @endif

                                @if ($deleteAction->isVisible())
                                    <div class="w-8"></div>
                                @endif

                                <span class="sr-only">
                                    {{ __('filament-table-repeater::components.repeater.row_actions.label') }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="w-full flex flex-col overflow-hidden rounded-b-xl">
                    @if (count($containers))
                        @foreach ($containers as $uuid => $row)
                            @php
                                $hasBoxRepeater = false;
                            @endphp

                            <div
                                wire:key="{{ $this->getId() }}.{{ $row->getStatePath() }}.{{ $field::class }}.item"
                                x-sortable-item="{{ $uuid }}"
                                class="flex flex-col justify-end items-center min-h-12 overflow-hidden border-b border-gray-950/5 dark:border-white/20"
                            >
                                <div class="w-full flex justify-end items-center min-h-12 overflow-hidden border-b border-gray-950/5 dark:border-white/20">
                                    @foreach($row->getComponents() as $cell)
                                        @if($cell instanceof \Wsmallnews\Support\Forms\Fields\BoxRepeater)
                                            @php
                                                $hasBoxRepeater = true;
                                            @endphp
                                            @continue;
                                        @endif

                                        @if(! $cell instanceof \Filament\Forms\Components\Hidden && ! $cell->isHidden())
                                            <div
                                                @php
                                                    $cellKey = method_exists($cell, 'getName') ? $cell->getName() : $cell->getId();
                                                @endphp

                                                @class([
                                                    'px-3 py-2 font-medium',
                                                    'ltr:rounded-tl-xl rtl:rounded-tr-xl' => $loop->first,
                                                    'ltr:rounded-tr-xl rtl:rounded-tl-xl' => $loop->last && ! $hasActions,
                                                    $columnCsses[$cellKey] ?? '',
                                                    match($getHeadersAlignment()) {
                                                        'center' => 'text-center',
                                                        'right' => 'text-right rtl:text-left',
                                                        default => 'text-left rtl:text-right'
                                                    }
                                                ])
                                                @if (
                                                    $columnWidths &&
                                                    isset($columnWidths[$cellKey])
                                                )
                                                    style="width: {{ $columnWidths[$cellKey] }}"
                                                @endif
                                            >
                                                {{ $cell }}
                                            </div>
                                        @else
                                            {{ $cell }}
                                        @endif
                                    @endforeach

                                    @if ($hasActions)
                                        {{-- <div class="p-2 w-px">
                                            <div class="flex items-center gap-x-3 md:justify-center"> --}}

                                        <div class="text-right px-3 py-2 font-medium">
                                            <div class="flex items-center gap-2 md:justify-center">
                                                @foreach ($visibleExtraItemActions as $extraItemAction)
                                                    <div>
                                                        {{ $extraItemAction(['item' => $uuid]) }}
                                                    </div>
                                                @endforeach

                                                @if ($reorderAction->isVisible())
                                                    <div x-sortable-handle class="shrink-0">
                                                        {{ $reorderAction }}
                                                    </div>
                                                @endif

                                                @if ($isReorderableWithButtons)
                                                    @if (! $loop->first)
                                                        <div>
                                                        {{ $moveUpAction(['item' => $uuid]) }}
                                                        </div>
                                                    @endif

                                                    @if (! $loop->last)
                                                        <div>
                                                        {{ $moveDownAction(['item' => $uuid]) }}
                                                        </div>
                                                    @endif
                                                @endif

                                                @if ($cloneAction->isVisible())
                                                    <div>
                                                    {{ $cloneAction(['item' => $uuid]) }}
                                                    </div>
                                                @endif

                                                @if ($deleteAction->isVisible())
                                                    <div>
                                                    {{ $deleteAction(['item' => $uuid]) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if ($hasBoxRepeater)
                                    @foreach($row->getComponents() as $cell)
                                        @if(!$cell instanceof \Wsmallnews\Support\Forms\Fields\BoxRepeater)
                                            @continue;
                                        @endif

                                        @if($cell->getIsFusionLayout())
                                            {{ $cell }}
                                        @else
                                            <div class="w-full flex flex-col min-h-12 pl-10 py-2.5 pr-2.5"
                                                wire:key="{{ $this->getId() }}.{{ $row->getStatePath() }}.{{ $field::class }}.{{ $cell->getName() }}.box-repeater-item"
                                                x-sortable-item="{{ $uuid }}"
                                            >
                                                {{ $cell }}
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="flex justify-center items-center min-h-12 overflow-hidden border-b border-gray-950/5 dark:border-white/20">
                            {{ $emptyLabel ?: __('filament-table-repeater::components.repeater.empty.label') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($addAction->isVisible())
            <div class="relative flex justify-center">
                {{ $addAction }}
            </div>
        @endif
    </div>
</x-dynamic-component>
