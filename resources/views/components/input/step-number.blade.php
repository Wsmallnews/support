@props([
    'min' => 0,
    'max' => 9999,
    'step' => 1,
    'decimalPoints' => 0,

    // control = 表单控件皮（边框 + 可编辑输入框）；bare = 裸步进器（− n +，样式由调用处给）
    'variant' => 'control',

    // bare：减号到下限的行为 —— disable = 置灰禁用（纯步进器语义，默认）；
    // dispatch = 保持可点并派发 sn-stepper-min（调用方接「减到下限 = 取消选择」类语义，附加项用）
    'minBehavior' => 'disable',

    // bare：数字是否可编辑（false = 纯展示 span，附加项等紧凑场景用）
    'editable' => true,

    // bare：−/+ 按钮形状类（默认只给形状尺寸，颜色/状态类由调用处经这两个 prop 追加）
    'buttonClass' => 'grid size-7 shrink-0 cursor-pointer place-items-center rounded-full',
    // bare：数字节点类
    'valueClass' => 'w-6 text-center text-sm font-semibold tabular-nums',
])

@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;

    $isControl = $variant === 'control';
@endphp

<div
    {{-- 立即加载（不用 visible）：步进器常藏在 x-show 里（附加项选中才显示），
         visible 的 IntersectionObserver 对隐藏元素不触发，选中后存在点击无响应的死区窗口 --}}
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
        {{-- 注意① filament 组件标签上的 :attr 会被 Blade 当 prop 绑定做 PHP 求值，Alpine 表达式必须用 x-bind: 前缀穿透；
             注意② @if 不能写在组件标签属性区（会打断组件解析输出原始标签），条件属性用完整标签二选一 --}}
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
        {{-- 裸步进器：+ 到上限恒禁用；− 到下限行为由 min-behavior 决定（disable 置灰禁用 / dispatch 保持可点派事件） --}}
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
