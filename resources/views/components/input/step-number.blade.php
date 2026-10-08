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
                icon="heroicon-m-minus"
                @click="decrement()"
            />
        </x-slot>
        <x-filament::input
            type="text"
            x-bind:value="currentVal"
            @input="updateValue($event)"
        />
        <x-slot name="suffix">
            <x-filament::icon-button
                class="text-gray-600"
                icon="heroicon-m-plus"
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
            init() {
                // 从服务端渲染的输入框取初始值（wire:model 经 x-modelable 回填可能晚于 init）
                const raw = this.$el.querySelector('input')?.value;
                const num = Number(raw);
                this.currentVal = this.clamp(raw !== '' && Number.isFinite(num) ? num : this.minVal);
            },
            clamp(num) {
                const clamped = Math.min(this.maxVal, Math.max(this.minVal, num));
                // toFixed 返回字符串，必须转回 number，否则后续 + 变字符串拼接
                return Number(clamped.toFixed(this.decimalPoints));
            },
            updateValue(event) {        // 输入框直接输入时触发
                const num = Number(event.target.value);
                this.currentVal = this.clamp(Number.isNaN(num) ? this.minVal : num);
                // 强制更新输入框显示的值（非数字输入回退为钳制后的值）
                event.target.value = this.currentVal;
            },
            decrement () {
                this.currentVal = this.clamp(Number(this.currentVal) - this.step);
            },
            increment () {
                this.currentVal = this.clamp(Number(this.currentVal) + this.step);
            }
        }
    }
</script>
@endassets