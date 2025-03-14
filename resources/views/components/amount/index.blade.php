@props([
    'amount',
])

{{-- 价格金额，高亮主题色 --}}
<div>
    <span class="text-sm text-primary-600 leading-5">{{ sn_currency()->getSymbol() }}</span>
    <span class="text-2xl font-bold text-primary-600 leading-6">{{ sn_currency()->format($amount) }}</span>
</div>