@props([
    'identifiable' => null,
    'action' => null,
])

@php
    use Filament\Support\Icons\HeroIcon;
    use Wsmallnews\Support\Contracts\HasSnIdentifiable;
    use Wsmallnews\Support\Exceptions\IdentifiableException;

    if (!$identifiable instanceof HasSnIdentifiable) {
        throw new IdentifiableException(get_class($identifiable) . ' model must implement `\Wsmallnews\Support\Contracts\HasSnIdentifiable` interface.');
    }
@endphp

<div class="w-full flex items-center gap-4 p-4 rounded-2xl
            bg-white border border-purple-100
            hover:border-purple-300 hover:shadow-lg hover:shadow-purple-100/40
            cursor-pointer transition-all duration-200
            group"
    @if($action) {{ $action->getLivewireClickHandler() }} @endif
>
    {{-- Avatar --}}
    <div class="relative shrink-0">
        @if ($identifiable->getSnAvatarUrl())
            <img
                src="{{ files_url($identifiable->getSnAvatarUrl()) }}"
                alt="{{ $identifiable->getSnName() }}"
                loading="lazy"
                class="size-12 rounded-full object-cover ring-2 ring-purple-100
                       group-hover:ring-purple-300 transition-all duration-200"
            />
        @else
            <div class="sn-image-placeholder">
                <x-filament::icon :icon="Heroicon::UserCircle" class="w-10 h-10" aria-hidden="true" />
            </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-purple-900 truncate leading-snug">
            {{ $identifiable->getSnName() }}
        </p>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1">
            @if ($identifiable->getSnMobile())
                <span class="inline-flex items-center gap-1 text-xs text-purple-400">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                    {{ $identifiable->getSnMobile() }}
                </span>
            @endif

            @if ($identifiable->getSnEmail())
                <span class="inline-flex items-center gap-1 text-xs text-purple-400 truncate max-w-[160px]">
                    <svg class="size-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    {{ $identifiable->getSnEmail() }}
                </span>
            @endif
        </div>
    </div>

    {{-- Chevron --}}
    <div class="shrink-0 text-purple-300/0 group-hover:text-purple-400 transition-all duration-200 group-hover:-translate-x-0.5">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
    </div>
</div>