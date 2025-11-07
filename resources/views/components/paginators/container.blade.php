@props([
    'pageType',
    'pageInfo',
    'pageName',
    'paginatorLink',
])

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    {{ $slot }}
    <x-sn-support::paginators :page-type="$pageType" :page-info="$pageInfo" :paginator-link="$paginatorLink" :page-name="$pageName" />
</div>
