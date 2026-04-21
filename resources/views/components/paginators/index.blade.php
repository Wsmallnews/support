@props([
    'pageType',
    'pageInfo',
    'pageName',
    'paginatorLink'
])

<div class="w-full mx-auto p-4">
    @if ($pageType == 'scroll')
        @if ($pageInfo['load_status'] == 'loading')
            <div class="sn-descript-text flex justify-center items-center gap-2" x-intersect="$wire.nextPage('{{ $pageName }}')">
                <x-filament::loading-indicator class="size-5" /> 正在加载更多
            </div>
        @elseif ($pageInfo['load_status'] == 'empty')
            <div class="sn-descript-text flex justify-center items-center" >
                暂没有更多数据
            </div>
        @elseif ($pageInfo['load_status'] == 'nomore')
            <div class="sn-descript-text flex justify-center items-center" >
                已经到底啦
            </div>
        @endif
    @elseif ($pageType == 'manual')
        <div class="sn-tip-text relative flex items-center gap-2">
            <div class="w-8 inline-block">
                <div class="h-0.25 w-8 border-b border-gray-400 absolute top-1/2"></div>
            </div>

            @if ($pageInfo['load_status'] == 'loading')
                <div class="flex justify-center items-center gap-2" wire:loading.flex wire:target="nextPage({{ $pageName }})">
                    <x-filament::loading-indicator class="size-4 inline-block" /> 正在加载更多
                </div>
                <div wire:loading.remove wire:target="nextPage({{ $pageName }})">
                    <span class="cursor-pointer" wire:click="nextPage('{{ $pageName }}')">展开更多</span>
                </div>
            @elseif ($pageInfo['load_status'] == 'empty')
                <span>暂没有更多数据</span>
            @elseif ($pageInfo['load_status'] == 'nomore')
                <span>已经到底啦</span>
            @endif

            @if ($pageInfo['load_status'] != 'empty')
                <span class="cursor-pointer" @click="$dispatch('hidden')">收起</span>
            @endif
        </div>
    @elseif ($pageType == 'paginator')
        {!! $paginatorLink !!}
    @endif
</div>