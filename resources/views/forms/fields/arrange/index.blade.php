@php
    use Filament\Support\Facades\FilamentView;

    $tableFields = $getTableFields();

    $record = $getRecord();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="space-y-4"
        @if (FilamentView::hasSpaMode())
            ax-load="visible || event (ax-modal-opened)"
        @else
            ax-load
        @endif
        wire:ignore
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('forms-arrange', 'wsmallnews/support') }}"
        x-data="arrangeFormField({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            arrangeToRecursionKey: '{{ $getArrangeToRecursionKey() }}',
            tableFields: @js($tableFields)
        })"
        x-ignore
    >
        <template x-for="(arrange, index) in arranges" :key="'arrange' + index">
            <div class="rounded-xl p-4 bg-gray-50 text-gray-950 ring-1 ring-gray-950/10 dark:ring-white/20 dark:bg-white/5 dark:text-white">
                <div class="w-fit flex items-center">
                    <x-filament::input.wrapper
                        {{-- :valid="! $errors->has($statePath)" --}}
                        :attributes="
                            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                                ->class(['fi-fo-text-input overflow-hidden sm:w-full max-w-80'])
                        "
                    >
                        <x-filament::input
                            :placeholder="$getArrangePlaceholder()"
                            type="text"
                            x-model="arrange.name"
                        />
                    </x-filament::input.wrapper>
                    <x-heroicon-m-trash class="h-5 w-5 text-danger-600 ml-2" @click="deleteArrange(index)" />
                </div>

                <div class="flex">
                    <template x-if="arrange.children.length">
                        <div class="w-5 h-10 border-l-2 border-b-2 ml-3 z-0"></div>
                    </template>
                    <div class="flex flex-wrap content-between items-center mt-5">
                        <template x-for="(child, idx) in arrange.children" :key="'arrangechild' + idx">
                            <div class="sm:w-full lg:max-w-40 mr-3 mb-3 flex items-center">
                                <x-filament::input.wrapper
                                    :attributes="
                                        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                                            ->class(['w-full'])
                                    "
                                >
                                    <x-filament::input
                                        :placeholder="$getArrangeChildPlaceholder()"
                                        type="text"
                                        x-model="child.name"
                                    />
                                </x-filament::input.wrapper>

                                <x-heroicon-m-trash class="h-5 w-5 text-danger-600 ml-2" @click="deleteChildrenArrange(index, idx)" />
                            </div>
                        </template>

                        <x-filament::link
                            class="ml-2.5 mb-3"
                            @click="addChildrenArrange(index)"
                        >
                            {{ $getChildAddActionLabel() }}
                        </x-filament::link>
                    </div>

                </div>
            </div>
        </template>

        <x-filament::button @click="addArrange">
            {{ $getAddActionLabel() }}
        </x-filament::button>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-ta-header flex flex-col gap-3 p-4 sm:px-6 sm:flex-row sm:items-center">
                <div class="grid gap-y-1 text-sm">
                    规格组合
                </div>
            </div>
            <div class="fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
                <template x-if="recursions.length">
                    <x-filament-tables::table>
                        <thead>
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <template x-for="(arrange, i) in arranges" :key="'arrange-head' + i">
                                    <template x-if="arrange.children.length">
                                        <x-filament-tables::header-cell>
                                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white" x-text="arrange.name"></span>
                                        </x-filament-tables::header-cell>
                                    </template>
                                </template>

                                <template x-for="(field, i) in tableFields">
                                    <x-filament-tables::header-cell>
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white" x-text="field.label"></span>
                                    </x-filament-tables::header-cell>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            <template x-for="(recursion, rskey) in recursions" :key="'recursion' + rskey">
                                <x-filament-tables::row>
                                    <x-dynamic-component :component="$getTableFieldsView()" :tableFields="$tableFields" :data="$getTableFieldsViewData()" />
                                </x-filament-tables::row>
                            </template>
                        </tbody>
                    </x-filament-tables::table>
                </template>
                <template x-if="!recursions.length">
                    <x-filament-tables::empty-state heading="请先设置规格信息" icon="lucide-shapes"></x-filament-tables::empty-state>
                </template>
            </div>
        </div>


        {{-- <div class="overflow-hidden w-full overflow-x-auto rounded-md  mt-4">
            border border-neutral-300 dark:border-neutral-700
            <template x-if="recursions.length">
                <x-filament-tables::table class="border-x border-b">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <template x-for="(arrange, i) in arranges" :key="'arrange-head' + i">
                                <template x-if="arrange.children.length">
                                    <x-filament-tables::header-cell>
                                        <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white" x-text="arrange.name"></span>
                                    </x-filament-tables::header-cell>
                                </template>
                            </template>

                            <template x-for="(field, i) in tableFields">
                                <x-filament-tables::header-cell>
                                    <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white" x-text="field.label"></span>
                                </x-filament-tables::header-cell>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">

                        <template x-for="(recursion, rskey) in recursions" :key="'recursion' + rskey">
                            <x-filament-tables::row>
                                <x-dynamic-component :component="$getTableFieldsView()" :tableFields="$tableFields" :data="$getTableFieldsViewData()" />
                            </x-filament-tables::row>
                        </template>
                    </tbody>
                </x-filament-tables::table>
            </template>
            <template x-if="!recursions.length">
                <x-filament-tables::empty-state heading="请先设置规格信息" icon="lucide-shapes"></x-filament-tables::empty-state>
            </template>
        </div> --}}
    </div>
</x-dynamic-component>