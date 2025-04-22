@props([
    'min' => 0,
    'max' => 9999,
    'step' => 1,
    'decimalPoints' => 0
])

<div x-data="numberManager({
        minVal: {{$min}},
        maxVal: {{$max}},
        step: {{$step}},
        decimalPoints: {{$decimalPoints}}
    })" 
    x-modelable="currentVal" {{ $attributes}}
    x-on:dblclick.prevent
>
    <x-filament::input.wrapper>
        <x-slot name="prefix">
            <x-filament::icon-button
                class="text-gray-600"
                icon="monoicon-remove"
                @click="decrement()"
            />
        </x-slot>
        <x-filament::input
            type="text"
            x-model="currentVal.toFixed(decimalPoints)"
        />
        <x-slot name="suffix">
            <x-filament::icon-button
                class="text-gray-600"
                icon="monoicon-add"
                @click="increment()"
            />
        </x-slot>
    </x-filament::input.wrapper>
</div>

@assets
<script>
    function numberManager({ minVal, maxVal, step, decimalPoints }) {
        return {
            minVal,
            maxVal,
            step,
            decimalPoints,
            currentVal: 0,
            decrement () {
                this.currentVal = Math.max(this.minVal, this.currentVal - this.step)
            },
            increment () {
                this.currentVal = Math.min(this.maxVal, this.currentVal + this.step)
            }
        }
    }
</script>
@endassets