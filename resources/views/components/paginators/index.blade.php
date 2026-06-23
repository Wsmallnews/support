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
                <x-filament::loading-indicator class="size-5" /> {{ __('sn-support::support.loading_more') }}
            </div>
        @elseif ($pageInfo['load_status'] == 'empty')
            <div class="sn-descript-text flex justify-center items-center" >
                {{ __('sn-support::support.no_more_data') }}
            </div>
        @elseif ($pageInfo['load_status'] == 'nomore')
            <div class="sn-descript-text flex justify-center items-center" >
                {{ __('sn-support::support.reached_bottom') }}
            </div>
        @endif
    @elseif ($pageType == 'manual')
        <div class="sn-tip-text relative flex items-center gap-2">
            <div class="w-8 inline-block">
                <div class="h-0.25 w-8 border-b border-gray-400 absolute top-1/2"></div>
            </div>

            @if ($pageInfo['load_status'] == 'loading')
                <div class="flex justify-center items-center gap-2" wire:loading.flex wire:target="nextPage('{{ $pageName }}')">
                    <x-filament::loading-indicator class="size-4 inline-block" /> {{ __('sn-support::support.loading_more') }}
                </div>
                <div wire:loading.remove wire:target="nextPage('{{ $pageName }}')">
                    <span class="cursor-pointer" wire:click="nextPage('{{ $pageName }}')">{{ __('sn-support::support.load_more') }}</span>
                </div>
            @elseif ($pageInfo['load_status'] == 'empty')
                <span>{{ __('sn-support::support.no_more_data') }}</span>
            @elseif ($pageInfo['load_status'] == 'nomore')
                <span>{{ __('sn-support::support.reached_bottom') }}</span>
            @endif

            @if ($pageInfo['load_status'] != 'empty')
                <span class="cursor-pointer" @click="$dispatch('hidden')">{{ __('sn-support::support.collapse') }}</span>
            @endif
        </div>
    @elseif ($pageType == 'paginator')
        {!! $paginatorLink !!}
    @endif
</div>