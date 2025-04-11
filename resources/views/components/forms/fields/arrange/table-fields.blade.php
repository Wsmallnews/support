@props([
    'tableFields',
    'data',
])

<template x-if="recursion.arrange_texts">
    <template x-for="(arrange_text, j) in recursion.arrange_texts" :key="'at' + j">
        <x-filament-tables::cell>
            <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4">
                <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white" x-text="arrange_text"></span>
            </div>
        </x-filament-tables::cell>
    </template>
</template>

@if ($tableFields)
    @foreach ($tableFields as $field)
        <x-filament-tables::cell>
            <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4" x-modelable="value" x-model="recursion['{{ $field['field'] }}']" x-data="{ value: 0 }" >
                <x-sn-support::forms.fields.arrange.fields :field="$field" />
            </div>
        </x-filament-tables::cell>
    @endforeach
@endif


{{-- <template x-if="tableFields">
    <template x-for="(field, i) in tableFields" :key="'art' + i">
        <x-filament-tables::cell>
            <div class="fi-ta-text grid w-full gap-y-1 px-3 py-4" x-modelable="value" x-model="recursion[field.field]" x-data="{ value: 0 }" >
                <x-sn-support::forms.fields.arrange.fields />
            </div>
        </x-filament-tables::cell>
    </template>
</template> --}}