@props([
    'amount',
])

{{-- 价格金额，高亮主题色 --}}
<div {{ $attributes->class(['flex items-end']) }}>
    <span class="text-sm font-bold mr-1 text-primary-600 leading-5">{{ sn_currency()->getSymbol() }}</span>
    <span class="text-2xl font-bold text-primary-600 leading-6">{{ sn_currency()->format($amount) }}</span>
</div>