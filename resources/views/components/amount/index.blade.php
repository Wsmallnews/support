@props([
    'amount' => null,
    'color' => 'primary',
    'symbolSize' => 'text-sm',
    'amountSize' => 'text-2xl',
    'currency' => null,
])

@php
    // Emphasised amount (small symbol, large figure); colour comes from sn_text_color().
    $parts = sn_money()->formatParts($amount, $currency);
    $textColor = sn_text_color($color);
@endphp

<span {{ $attributes->class(['flex items-baseline font-bold', $textColor['class']]) }} @style($textColor['style'])>
    <span class="{{ $symbolSize }} mr-0.5 leading-none">{{ $parts['symbol'] }}</span>
    <span class="{{ $amountSize }} leading-none">{{ $parts['amount'] }}</span>
</span>
