@php
    use Filament\Support\Facades\FilamentView;

    $tableFields = json_encode($getTableFields());

    $record = $getRecord();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>

    <div class=""
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
            tableFields: {{ $tableFields }}

            {{-- arranges: $wire.data.{{ $getArrangeName() }},
            recursions: $wire.data.{{ $getRecursionName() }}, --}}
            {{-- arranges: {{ $record->{$getArrangeName()} ?? [] }},
            recursions: {{ $record->{$getRecursionName()} ?? [] }}, --}}
        })"
        x-ignore
    >
        <template x-for="(arrange, index) in arranges" :key="'arrange' + index">
            <div class="border rounded p-2.5 bg-gray-100 mb-4">
                <div class="w-fit relative">
                    <x-filament::input.wrapper
                        :valid="! $errors->has($statePath)"
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
                    <x-jam-close-circle class="h-5 w-5 text-primary-600 absolute -top-2.5 -right-2.5" @click="deleteArrange(index)" />
                </div>

                <div class="flex">
                    <template x-if="arrange.children.length">
                        <div class="w-5 h-10 border-l-2 border-b-2 ml-3"></div>
                    </template>
                    <div class="flex flex-wrap content-between items-center mt-5">
                        <template x-for="(child, idx) in arrange.children" :key="'arrangechild' + idx">
                            <div class="sm:w-full lg:max-w-40 mr-3 mb-3 relative">
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
                                <x-jam-close-circle class="h-5 w-5 text-primary-600 absolute -top-2.5 -right-2.5" @click="deleteChildrenArrange(index, idx)" />
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

        <div class="overflow-hidden w-full overflow-x-auto rounded-md border border-neutral-300 dark:border-neutral-700 mt-4">
            <table class="w-full text-left text-sm text-neutral-600 dark:text-neutral-300">
                <thead class="border-b border-neutral-300 bg-neutral-50 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-white">
                    <tr>
                        <template x-for="(arrange, i) in arranges" :key="'arrange-head' + i">
                            <template x-if="arrange.children.length">
                                <th scope="col" class="p-4" x-text="arrange.name"></th>
                            </template>
                        </template>

                        <template x-for="(field, i) in tableFields">
                            <th x-text="field.label"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-300 dark:divide-neutral-700">
                    <template x-for="(recursion, rskey) in recursions" :key="'recursion' + rskey">
                        <tr>
                            <x-dynamic-component :component="$getTableFieldsView()" :data="$getTableFieldsViewData()" />
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</x-dynamic-component>