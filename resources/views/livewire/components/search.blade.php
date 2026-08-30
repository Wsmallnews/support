<div
    class="w-full relative"
    x-data="{ open: false }"
    @click.outside="open = false"
>
    <input
        type="search"
        wire:model.live.debounce.{{ $debounce }}="query"
        placeholder="{{ $placeholder }}"
        @focus="open = true"
        @input="open = true"
        @keydown.escape="open = false"
        class="w-full rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 px-3.5 py-2.5 text-sm sn-content-text outline-none focus:border-primary-400 dark:focus:border-primary-500 focus:ring-2 focus:ring-primary-500/30"
    />

    @if (trim((string) $query) !== '')
        <div
            class="absolute z-20 mt-2 w-full max-h-96 overflow-y-auto rounded-md border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg"
            x-show="open"
        >
            @forelse ($groups as $group => $results)
                <div class="py-1">
                    <div class="sn-tip-text px-4 py-1.5 bg-gray-50 dark:bg-gray-800/60 sticky top-0">
                        {{ $group }}
                    </div>

                    @foreach ($results as $result)
                        @php
                            $rowClass = 'flex items-center gap-3 px-4 py-2.5 sn-hover sn-link';
                        @endphp

                        @if ($result->url)
                            <a href="{{ $result->url }}" class="{{ $rowClass }}">
                                @include('sn-support::livewire.components.search-result', ['result' => $result])
                            </a>
                        @else
                            <div class="{{ $rowClass }}">
                                @include('sn-support::livewire.components.search-result', ['result' => $result])
                            </div>
                        @endif
                    @endforeach
                </div>
            @empty
                <x-sn-support::empty-state
                    :contained="false"
                    icon="heroicon-m-magnifying-glass"
                    icon-color="gray"
                    :heading="__('sn-support::search.empty')"
                    :description="__('sn-support::search.empty_tip')"
                />
            @endforelse
        </div>
    @endif
</div>
