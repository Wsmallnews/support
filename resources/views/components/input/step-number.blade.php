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
            x-bind:value="currentVal"
            @input="updateValue($event)"
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
            updateValue(event) {        // 输入框直接输入时触发
                // 提取输入值并转换为数字
                let num = Number(event.target.value);
                if (isNaN(num)) num = this.minVal;

                // 限制范围
                if (num < this.minVal) {
                    num = this.minVal;
                } else if (num > this.maxVal) {
                    num = this.maxVal;
                }

                // 更新 value
                this.currentVal = num.toFixed(decimalPoints)
                // 强制更新输入框显示的值（防止显示异常）
                event.target.value = this.currentVal;
            },
            decrement () {
                this.currentVal = Math.max(this.minVal, this.currentVal - this.step).toFixed(decimalPoints)
            },
            increment () {
                this.currentVal = Math.min(this.maxVal, this.currentVal + this.step).toFixed(decimalPoints)
            }
        }
    }
</script>
@endassets