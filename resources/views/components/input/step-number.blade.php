@props([
    'min' => 0,
    'max' => 9999,
    'step' => 1,
    'decimalPoints' => 0,

    // control: form control skin. bare: plain stepper, styled by the caller.
    'variant' => 'control',

    // bare, at the minimum: disable greys the button out, dispatch keeps it clickable and
    // fires sn-stepper-min so callers can treat it as a deselect.
    'minBehavior' => 'disable',

    // bare: whether the number is editable (false renders a plain span)
    'editable' => true,

    // bare: shape classes for the -/+ buttons; colour and state come from the caller
    'buttonClass' => 'grid size-7 shrink-0 cursor-pointer place-items-center rounded-full',
    // bare: classes for the number node
    'valueClass' => 'w-6 text-center text-sm font-semibold tabular-nums',
])

@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;

    $isControl = $variant === 'control';
@endphp

<div
    {{-- Load immediately, not on visible: the stepper often sits inside x-show, where
    visible's IntersectionObserver never fires and clicks do nothing. --}}
    x-load
    x-load-src="{{ FilamentAsset::getAlpineComponentSrc('sn-support-components-number-stepper', 'wsmallnews/support') }}"
    x-data="numberStepper({
        minVal: {{$min}},
        maxVal: {{$max}},
        step: {{$step}},
        decimalPoints: {{$decimalPoints}}
    })"
    x-modelable="currentVal"
    {{ $attributes }}
    x-on:dblclick.prevent
>
    @if ($isControl)
        {{-- :attr on a Filament component is evaluated as a PHP prop, so Alpine expressions
        need the x-bind: prefix. And @if inside a tag breaks component parsing: use two tags. --}}
        <x-filament::input.wrapper>
            <x-slot name="prefix">
                @if ($minBehavior === 'dispatch')
                    <x-filament::icon-button
                        class="text-gray-600"
                        :icon="Heroicon::Minus"
                        @click="decrement()"
                    />
                @else
                    <x-filament::icon-button
                        class="text-gray-600"
                        :icon="Heroicon::Minus"
                        x-bind:disabled="currentVal <= minVal"
                        x-bind:class="currentVal <= minVal ? 'opacity-40 cursor-not-allowed' : ''"
                        @click="decrement()"
                    />
                @endif
            </x-slot>
            <x-filament::input
                type="text"
                class="text-center"
                x-bind:value="currentVal"
                @input="updateValue($event)"
            />
            <x-slot name="suffix">
                <x-filament::icon-button
                    class="text-gray-600"
                    :icon="Heroicon::Plus"
                    x-bind:disabled="currentVal >= maxVal"
                    x-bind:class="currentVal >= maxVal ? 'opacity-40 cursor-not-allowed' : ''"
                    @click="increment()"
                />
            </x-slot>
        </x-filament::input.wrapper>
    @else
        {{-- Bare stepper: + is always disabled at the max; - behaviour comes from min-behavior --}}
        <div class="flex items-center gap-1.5">
            <button
                type="button"
                class="{{ $buttonClass }}"
                @if ($minBehavior !== 'dispatch')
                    :disabled="currentVal <= minVal"
                    :class="currentVal <= minVal ? 'opacity-40 cursor-not-allowed' : ''"
                @endif
                @click="decrement()"
            >
                <x-filament::icon :icon="Heroicon::Minus" class="size-4" />
            </button>

            @if ($editable)
                <input
                    type="text"
                    class="{{ $valueClass }}"
                    x-bind:value="currentVal"
                    @input="updateValue($event)"
                />
            @else
                <span class="{{ $valueClass }}" x-text="currentVal"></span>
            @endif

            <button
                type="button"
                class="{{ $buttonClass }}"
                :disabled="currentVal >= maxVal"
                :class="currentVal >= maxVal ? 'opacity-40 cursor-not-allowed' : ''"
                @click="increment()"
            >
                <x-filament::icon :icon="Heroicon::Plus" class="size-4" />
            </button>
        </div>
    @endif
</div>
