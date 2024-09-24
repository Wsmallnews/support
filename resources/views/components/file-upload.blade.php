@php
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Facades\FilamentView;

    // $statePath = $getStatePath();
    // $alignment = $getAlignment() ?? Alignment::Start;

    // if (! $alignment instanceof Alignment) {
    //     $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
    // }
@endphp


@props([
    'acceptedFileTypes' => ['image/*'],
    'imagePreviewHeight' => 100,
    'removeUploadedFileButtonPosition' => 'left',     // 移除按钮的位置 'left', 'center', 'right' / 'bottom'
    'shouldAppendFiles' => true,                    // 多图上传时，往后追加，而不是插入到前面
    'shouldOrientImagesFromExif' => true,           // 允许使用 exif 插件
    'uploadButtonPosition' => 'left',                 // 上传过程按钮的位置 'left', 'center', 'right' / 'bottom'
    'uploadingMessage' => '',                     // 上传中信息
    'uploadProgressIndicatorPosition' => 'right',                 // 上传进度指示器按钮的位置 'left', 'center', 'right' / 'bottom'
    'maxFiles' => 9,
    'maxSize' => '10M',
    'minSize' => null,
    'isAvatar' => false,
    'isDeletable' => true,
    'isDisabled' => false,
    'isDownloadable' => false,
    'isMultiple' => false,
    'isOpenable' => false,
    'isPreviewable' => true,
    'isReorderable' => true,
])

@assets
@if(!$isMultiple)
    <style>
        .filepond--root {
            width: 100px;
        }
        .filepond--drop-label {
            width: 100px;
            height: 100px;
        }
    </style>
@else
    <style>
        .filepond--root {
            padding: 10px;
        }

        .filepond--drop-label {
            min-height: 30px !important;
            align-items: flex-start;
            justify-content: flex-start;
        }

        .filepond--drop-label label {
            width: 100px;
            height: 30px;
            padding: 5px !important;
            border: 1px solid rgba(var(--gray-950), 0.2);
            border-radius: 6px;
        }

        .filepond--list .filepond--item {
            width: 100px;
            margin-right: 10px !important;
            margin-bottom: 10px !important;
        }
    </style>
@endif
@endassets


{{-- <x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :label-sr-only="$isLabelHidden()"
> --}}
    <div
        @if (FilamentView::hasSpaMode())
            {{-- format-ignore-start --}}ax-load="visible || event (ax-modal-opened)"{{-- format-ignore-end --}}
        @else
            ax-load
        @endif
        ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('file-upload', 'filament/forms') }}"
        x-data="fileUploadFormComponent({
                    acceptedFileTypes: @js($acceptedFileTypes),
                    deleteUploadedFileUsing: async (fileKey) => {
                        {{-- return await $wire.deleteUploadedFile(@js($statePath), fileKey) --}}
                    },
                    getUploadedFilesUsing: async () => {
                        {{-- return await $wire.getFormUploadedFiles(@js($statePath)) --}}
                    },
                    hasImageEditor: false,
                    imagePreviewHeight: @js($imagePreviewHeight),
                    isAvatar: @js($isAvatar),
                    isDeletable: @js($isDeletable),
                    isDisabled: @js($isDisabled),
                    isDownloadable: @js($isDownloadable),
                    isMultiple: @js($isMultiple),
                    isOpenable: @js($isOpenable),
                    isPreviewable: @js($isPreviewable),
                    isReorderable: @js($isReorderable),


                    {{-- itemPanelAspectRatio: @js($getItemPanelAspectRatio()),
                    loadingIndicatorPosition: @js($getLoadingIndicatorPosition()), --}}
                    locale: @js(app()->getLocale()),

                    {{-- panelAspectRatio: @js($getPanelAspectRatio()),
                    panelLayout: @js($getPanelLayout()),
                    placeholder: @js($getPlaceholder()), --}}
                    maxFiles: @js($maxFiles),
                    maxSize: @js($maxSize ? "{$maxSize}KB" : null),
                    minSize: @js($minSize ? "{$minSize}KB" : null),

                    removeUploadedFileUsing: async (fileKey) => {
                        {{-- return await $wire.removeFormUploadedFile(@js($statePath), fileKey) --}}
                    },
                    removeUploadedFileButtonPosition: @js($removeUploadedFileButtonPosition),
                    reorderUploadedFilesUsing: async (files) => {
                        {{-- return await $wire.reorderFormUploadedFiles(@js($statePath), files) --}}
                    },
                    shouldAppendFiles: @js($shouldAppendFiles),
                    shouldOrientImageFromExif: @js($shouldOrientImagesFromExif),

                    {{-- state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }}, --}}

                    uploadButtonPosition: @js($uploadButtonPosition),
                    uploadingMessage: @js($uploadingMessage),

                    uploadProgressIndicatorPosition: @js($uploadProgressIndicatorPosition),
                    uploadUsing: (fileKey, file, success, error, progress) => {
                        $wire.upload(
                            {{-- `{{ $statePath }}.${fileKey}`, --}}
                            file,
                            () => {
                                success(fileKey)
                            },
                            error,
                            (progressEvent) => {
                                progress(true, progressEvent.detail.progress, 100)
                            },
                        )
                    },
                })"
        wire:ignore
        x-ignore
        {{
            $attributes
                ->merge([
                    // 'id' => $getId(),
                ], escape: false)
                ->class([
                    'fi-fo-file-upload flex flex-col gap-y-2 [&_.filepond--root]:font-sans',
                    // match ($alignment) {
                    //     Alignment::Start, Alignment::Left => 'items-start',
                    //     Alignment::Center => 'items-center',
                    //     Alignment::End, Alignment::Right => 'items-end',
                    //     default => $alignment,
                    // },
                ])
        }}
    >
        <div
        >
            <input
                x-ref="input"
                disabled="{{$isDisabled}}"
                multiple="{{$isMultiple}}"
                type="file"
            />
        </div>

        <div
            x-show="error"
            x-text="error"
            x-cloak
            class="text-sm text-danger-600 dark:text-danger-400"
        ></div>
    </div>
{{-- </x-dynamic-component> --}}
