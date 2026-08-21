<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Forms;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 上传表单组件工厂
 *
 * 两类上传组件：
 * - media*：通过 Spatie Media Library 管理（登记 media 表、挂模型集合）
 * - plain*：不经媒体库管理，直接存储到 disk，路径存入模型字段（存储位置由 disk 配置决定）
 *
 * 统一应用 sn-support.form_components.upload 配置。
 */
trait HasUploadComponents
{
    // ========================= 媒体库上传组件 =========================

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

    // ========================= 普通上传组件（不经媒体库管理） =========================

    /**
     * 创建一个普通图片上传组件（直接存储到 disk，路径存入模型字段）
     *
     * @param  string  $name  字段名称
     */
    public static function plainImageUpload(string $name): Forms\Components\FileUpload
    {
        $component = Forms\Components\FileUpload::make($name)
            ->image();

        return static::applyUploadConfig($component);
    }

    /**
     * 创建一个普通文件上传组件（直接存储到 disk，路径存入模型字段）
     *
     * @param  string  $name  字段名称
     */
    public static function plainFileUpload(string $name): Forms\Components\FileUpload
    {
        $component = Forms\Components\FileUpload::make($name);

        return static::applyUploadConfig($component);
    }

    // ========================= 通用配置 =========================

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
        // 面板布局 (compact, grid, compact circle)
        if (isset($config['panel_layout'])) {
            $component->panelLayout(function (Forms\Components\FileUpload $component) use ($config) {
                if ($component->isMultiple()) {
                    return $config['panel_layout'];
                }

                return 'compact';
            });
        }

        return $component;
    }
}
