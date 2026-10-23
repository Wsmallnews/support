<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Forms;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Upload component factories. media* variants go through Spatie Media Library; plain*
 * variants store straight to the disk and keep the path in a column. Both share the
 * form_components.upload config.
 */
trait HasUploadComponents
{
    // ------------------------- Media library uploads -------------------------

    /**
    * Spatie Media Library image upload.
    *
    * @param  string  $name  Column name
    * @param  string  $collection  Media collection name
    */
    public static function mediaImageUpload(string $name, string $collection): Forms\Components\SpatieMediaLibraryFileUpload
    {
        $component = Forms\Components\SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection)
            ->image();

        return static::applyUploadConfig($component);
    }

    /**
    * Spatie Media Library file upload.
    *
    * @param  string  $name  Column name
    * @param  string  $collection  Media collection name
    */
    public static function mediaFileUpload(string $name, string $collection): Forms\Components\SpatieMediaLibraryFileUpload
    {
        $component = Forms\Components\SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection);

        return static::applyUploadConfig($component);
    }

    // ------------------------- Plain uploads (no media library) -------------------------

    /**
    * Plain image upload: writes to the disk and keeps the path in the column.
    *
    * @param  string  $name  Column name
    */
    public static function plainImageUpload(string $name): Forms\Components\FileUpload
    {
        $component = Forms\Components\FileUpload::make($name)
            ->image();

        return static::applyUploadConfig($component);
    }

    /**
    * Plain file upload: writes to the disk and keeps the path in the column.
    *
    * @param  string  $name  Column name
    */
    public static function plainFileUpload(string $name): Forms\Components\FileUpload
    {
        $component = Forms\Components\FileUpload::make($name);

        return static::applyUploadConfig($component);
    }

    // ------------------------- Shared config -------------------------

    /** Apply the shared upload config. */
    protected static function applyUploadConfig(
        Forms\Components\FileUpload | Forms\Components\SpatieMediaLibraryFileUpload $component
    ): Forms\Components\FileUpload | Forms\Components\SpatieMediaLibraryFileUpload {
        $config = SupportUtils::getConfig('form_components.upload');

        // Disk
        $component->disk(SupportUtils::getFilesystemDisk());
        // Visibility
        if (isset($config['visibility'])) {
            $component->visibility($config['visibility']);
        }
        // Downloadable
        if (isset($config['downloadable'])) {
            $component->downloadable($config['downloadable']);
        }
        // Openable
        if (isset($config['openable'])) {
            $component->openable($config['openable']);
        }
        // Reorderable
        if (isset($config['reorderable'])) {
            $component->reorderable($config['reorderable']);
        }
        // Append mode
        if (isset($config['append_files'])) {
            $component->appendFiles($config['append_files']);
        }
        // Max files
        if (isset($config['max_files'])) {
            $component->maxFiles($config['max_files']);
        }
        // Max size
        if (isset($config['max_size'])) {
            $component->maxSize($config['max_size']);
        }
        // Image preview height
        if (isset($config['image_preview_height'])) {
            $component->imagePreviewHeight($config['image_preview_height']);
        }
        // Panel layout: compact, grid, compact circle
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
