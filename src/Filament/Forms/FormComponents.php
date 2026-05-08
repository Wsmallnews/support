<?php

namespace Wsmallnews\Support\Filament\Forms;

use Filament\Forms;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class FormComponents
{
    /**
     * 创建一个 Spatie Media Library 图片上传组件
     *
     * @param  string  $name  字段名称
     * @param  string  $collection  媒体集合名称
     */
    public static function mediaImageUpload(string $name, string $collection): Forms\Components\SpatieMediaLibraryFileUpload
    {
        $component = Forms\Components\SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection)
            ->image();

        return static::applyUploadConfig($component);
    }

    /**
     * 创建一个 Spatie Media Library 文件上传组件
     *
     * @param  string  $name  字段名称
     * @param  string  $collection  媒体集合名称
     */
    public static function mediaFileUpload(string $name, string $collection): Forms\Components\SpatieMediaLibraryFileUpload
    {
        $component = Forms\Components\SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection);

        return static::applyUploadConfig($component);
    }

    /**
     * 创建一个本地图片上传组件
     *
     * @param  string  $name  字段名称
     */
    public static function localImageUpload(string $name): Forms\Components\FileUpload
    {
        $component = Forms\Components\FileUpload::make($name)
            ->image();

        return static::applyUploadConfig($component);
    }

    /**
     * 创建一个本地文件上传组件
     *
     * @param  string  $name  字段名称
     */
    public static function localFileUpload(string $name): Forms\Components\FileUpload
    {
        $component = Forms\Components\FileUpload::make($name);

        return static::applyUploadConfig($component);
    }

    /**
     * 创建一个 Markdown 编辑器组件
     *
     * @param  string  $name  字段名称
     */
    public static function markdownEditor(string $name): Forms\Components\MarkdownEditor
    {
        $component = Forms\Components\MarkdownEditor::make($name);

        return static::applyEditorConfig($component);
    }

    /**
     * 创建一个富文本编辑器组件
     *
     * @param  string  $name  字段名称
     */
    public static function richEditor(string $name): Forms\Components\RichEditor
    {
        $component = Forms\Components\RichEditor::make($name);

        return static::applyEditorConfig($component);
    }

    /**
     * 为上传组件应用配置
     */
    protected static function applyUploadConfig(
        Forms\Components\FileUpload | Forms\Components\SpatieMediaLibraryFileUpload $component
    ): Forms\Components\FileUpload | Forms\Components\SpatieMediaLibraryFileUpload {
        $config = SupportUtils::getConfig('form_components.upload');

        // 默认磁盘
        $component->disk(SupportUtils::getFilesystemDisk());
        // 可见性
        if (isset($config['visibility'])) {
            $component->visibility($config['visibility']);
        }
        // 是否可下载
        if (isset($config['downloadable'])) {
            $component->downloadable($config['downloadable']);
        }
        // 是否可查看预览
        if (isset($config['openable'])) {
            $component->openable($config['openable']);
        }
        // 多图是否可排序
        if (isset($config['reorderable'])) {
            $component->reorderable($config['reorderable']);
        }
        // 多图追加模式
        if (isset($config['append_files'])) {
            $component->appendFiles($config['append_files']);
        }
        // 最大文件数量 多图有效
        if (isset($config['max_files'])) {
            $component->maxFiles($config['max_files']);
        }
        // 最大上传大小
        if (isset($config['max_size'])) {
            $component->maxSize($config['max_size']);
        }
        // 图片预览高度
        if (isset($config['image_preview_height'])) {
            $component->imagePreviewHeight($config['image_preview_height']);
        }

        return $component;
    }

    /**
     * 为编辑器组件应用配置
     */
    protected static function applyEditorConfig(
        Forms\Components\MarkdownEditor | Forms\Components\RichEditor $component
    ): Forms\Components\MarkdownEditor | Forms\Components\RichEditor {
        $config = SupportUtils::getConfig('form_components.editor');

        // 默认磁盘
        $component->fileAttachmentsDisk(SupportUtils::getFilesystemDisk());
        // 可见性
        if (isset($config['file_attachment']['visibility']) && $component instanceof Forms\Components\RichEditor) {
            $component->fileAttachmentsVisibility($config['file_attachment']['visibility']);
        }
        // 最大文件大小
        if (isset($config['file_attachment']['max_size'])) {
            $component->fileAttachmentsMaxSize($config['file_attachment']['max_size']);
        }
        // markdown 工具栏
        if (isset($config['markdown']['toolbar_buttons']) && $component instanceof Forms\Components\MarkdownEditor) {
            $component->toolbarButtons($config['markdown']['toolbar_buttons']);
        }
        // richtext 工具栏
        if (isset($config['richtext']['toolbar_buttons']) && $component instanceof Forms\Components\RichEditor) {
            $component->toolbarButtons($config['richtext']['toolbar_buttons']);
        }
        // 浮动工具栏
        if (isset($config['richtext']['floating_toolbars']) && $component instanceof Forms\Components\RichEditor) {
            $component->floatingToolbars($config['richtext']['floating_toolbars']);
        }
        // 文本颜色
        if (isset($config['richtext']['text_colors']) && $component instanceof Forms\Components\RichEditor) {
            $component->textColors($config['richtext']['text_colors']);
        }
        // 文本长度
        if (isset($config['max_length'])) {
            $component->maxLength($config['max_length']);
        }

        return $component;
    }
}
