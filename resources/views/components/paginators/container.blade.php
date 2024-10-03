@props([
    'pageType',
    'pageInfo',
    'pageName',
    'paginatorLink'
])

<div class="container mx-auto">
    {{ $slot }}

    {{-- 分页 --}}
    <div class="container">
        <x-sn-support::paginators :page-type="$pageType" :page-info="$pageInfo" :paginator-link="$paginatorLink" :page-name="$pageName" />

        {{-- @if ($pageType == 'paginator')
            {!! $paginatorLink !!}
        @else
            <livewire:sn-paginator :page-info="$pageInfo" :page-type="$pageType" :page-name="$pageName" />
        @endif --}}
    </div>
</div>
