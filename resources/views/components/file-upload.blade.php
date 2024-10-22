@props([
    'acceptedFileTypes' => ['image/*'],

    'isAvatar' => false,

    'isDeletable' => true,
    'isDisabled' => false,
    'isDownloadable' => false,
    'isMultiple' => false,
    'isOpenable' => false,
    'isPreviewable' => false,
    'isReorderable' => true,

    'imagePreviewHeight' => 100,

    'removeUploadedFileButtonPosition' => 'left',     // 移除按钮的位置 'left', 'center', 'right' / 'bottom'
    'uploadButtonPosition' => 'left',                 // 上传过程按钮的位置 'left', 'center', 'right' / 'bottom'
    'uploadingMessage' => '',                     // 上传中信息
    'uploadProgressIndicatorPosition' => 'right',                 // 上传进度指示器按钮的位置 'left', 'center', 'right' / 'bottom'

    'shouldAppendFiles' => true,                    // 多图上传时，往后追加，而不是插入到前面
    'shouldOrientImagesFromExif' => true,           // 允许使用 exif 插件
    'maxFiles' => 9,
    'maxSize' => '10M',
    'minSize' => null,
])


@php

use Filament\Support\Enums\Alignment;
use Filament\Support\Facades\FilamentView;

$default = [
    // 'aaa' => [
    //     'name' => 'aaaaa.png',
    //     // 'size' => 7719,
    //     'type' => 'image/png',
    //     'url' => 'https://unit.smallnews.top/storage/CDOdF7qpGJujHGolTW6TULjN9LBg9p8jO8Vrz3x4.png'
    // ],
    // 'bbb' => [
    //     'name' => 'bbbbb.png',
    //     // 'size' => 7719,
    //     // 'type' => 'image/png',
    // ]
    
];

// $default = [
//     'https://unit.smallnews.top/storage/CDOdF7qpGJujHGolTW6TULjN9LBg9p8jO8Vrz3x4.png'
// ]


$statePath = 'aaaaa';

@endphp


<div
    @if (FilamentView::hasSpaMode())
        {{-- format-ignore-start --}}ax-load="visible || event (ax-modal-opened)"{{-- format-ignore-end --}}
    @else
        ax-load
    @endif
    ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('components-file-upload', 'wsmallnews/support') }}"
    x-data="supportFileUploadComponent({
                acceptedFileTypes: @js($acceptedFileTypes),
                getUploadedFilesUsing: async () => {
                    {{-- return await $wire.getFormUploadedFiles(@js($statePath)) --}}
                    {{-- @sn todo 这里返回老图 --}}
                    return @js($default);
                },
                uploadUsing: (fileKey, file, success, error, progress) => {
                    $wire.upload(
                        `{{ $statePath }}`,         // @sn todo 这里先不要 .${fileKey}
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
                deleteUploadedFileUsing: async (fileKey) => {
                    return await $wire.deleteUploadedFile(@js($statePath), fileKey)
                },
                removeUploadedFileUsing: async (fileKey) => {
                    return await $wire.removeFormUploadedFile(@js($statePath), fileKey)
                },
                reorderUploadedFilesUsing: async (files) => {
                    return await $wire.reorderFormUploadedFiles(@js($statePath), files)
                },

                isAvatar: @js($isAvatar),
                isDeletable: @js($isDeletable),
                isDisabled: @js($isDisabled),
                isDownloadable: @js($isDownloadable),
                isMultiple: @js($isMultiple),
                isOpenable: @js($isOpenable),
                isPreviewable: @js($isPreviewable),
                isReorderable: @js($isReorderable),

                imagePreviewHeight: @js($imagePreviewHeight),

                locale: @js(app()->getLocale()),
                shouldAppendFiles: @js($shouldAppendFiles),
                shouldOrientImageFromExif: @js($shouldOrientImagesFromExif),

                maxFiles: @js($maxFiles),
                maxSize: @js($maxSize ? "{$maxSize}KB" : null),
                minSize: @js($minSize ? "{$minSize}KB" : null),

                {{-- state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }}, --}}
                state: $wire.$entangle('{{$statePath}}'),

                {{-- 待确认参数 --}}
                uploadButtonPosition: @js($uploadButtonPosition),
                uploadingMessage: @js($uploadingMessage),
                uploadProgressIndicatorPosition: @js($uploadProgressIndicatorPosition),
                removeUploadedFileButtonPosition: @js($removeUploadedFileButtonPosition)
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
            {{$isDisabled ? 'disabled' : ''}}"
            {{$isMultiple ? 'multiple' : ''}}"
            type="file"
        />
    </div>

    {{-- <div
        x-show="error"
        x-text="error"
        x-cloak
        class="text-sm text-danger-600 dark:text-danger-400"
    ></div> --}}
</div>
