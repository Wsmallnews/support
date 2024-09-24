@props([
    'data',
])

<template x-if="recursion.arrange_texts">
    <template x-for="(arrange_text, j) in recursion.arrange_texts" :key="'at' + j">
        <td class="p-4" x-text="arrange_text"></td>
    </template>
</template>


{{-- <td class="p-4">
    <x-filament::input.wrapper

    >
        <x-sn-support::file-upload
        />
    </x-filament::input.wrapper>
</td> --}}

<td class="p-4">
    <x-filament::input.wrapper

    >
        <x-filament::input
            type="text"
            x-model="recursion.cost_price"
        />
    </x-filament::input.wrapper>
</td>

<td class="p-4">
    <x-filament::input.wrapper

    >
        <x-filament::input
            type="text"
            x-model="recursion.price"
        />
    </x-filament::input.wrapper>
</td>