@props([
    'amount' => null,
    'color' => 'primary',
    'symbolSize' => 'text-sm',
    'amountSize' => 'text-2xl',
    'currency' => null,
])

@php
    // 着重显示场景的金额组件（小符号 + 大数字）：颜色走 sn_text_color 双通道（色名预置类 / Filament 色板动态变量）
    $parts = sn_money()->formatParts($amount, $currency);
    $textColor = sn_text_color($color);
@endphp

<span {{ $attributes->class(['flex items-baseline font-bold', $textColor['class']]) }} @style($textColor['style'])>
    <span class="{{ $symbolSize }} mr-0.5 leading-none">{{ $parts['symbol'] }}</span>
    <span class="{{ $amountSize }} leading-none">{{ $parts['amount'] }}</span>
</span>
