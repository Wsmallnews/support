<?php

namespace Wsmallnews\Support\Filament\Forms;

use Filament\Forms;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class FormComponents
{
    public static function mediaImageUpload(string $name, string $collection, array $options = []): Forms\Components\SpatieMediaLibraryFileUpload
    {
        $config = static::mergeOptions(
            static::getConfig('media_image'),
            $options
        );

        $component = Forms\Components\SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection)
            ->image();

        static::applyUploadConfig($component, $config);

        return $component;
    }

    public static function mediaFileUpload(string $name, string $collection, array $options = []): Forms\Components\SpatieMediaLibraryFileUpload
    {
        $config = static::mergeOptions(
            static::getConfig('media_file'),
            $options
        );

        $component = Forms\Components\SpatieMediaLibraryFileUpload::make($name)
            ->collection($collection);

        static::applyUploadConfig($component, $config);

        return $component;
    }

    public static function localImageUpload(string $name, array $options = []): Forms\Components\FileUpload
    {
        $config = static::mergeOptions(
            static::getConfig('local_image'),
            $options
        );

        $component = Forms\Components\FileUpload::make($name)
            ->image();

        static::applyUploadConfig($component, $config);

        return $component;
    }

    public static function localFileUpload(string $name, array $options = []): Forms\Components\FileUpload
    {
        $config = static::mergeOptions(
            static::getConfig('local_file'),
            $options
        );

        $component = Forms\Components\FileUpload::make($name);

        static::applyUploadConfig($component, $config);

        return $component;
    }

    public static function markdownEditor(string $name, array $options = []): Forms\Components\MarkdownEditor
    {
        $config = static::mergeOptions(
            static::getConfig('markdown'),
            $options
        );

        $component = Forms\Components\MarkdownEditor::make($name);

        static::applyEditorConfig($component, $config);

        return $component;
    }

    public static function richEditor(string $name, array $options = []): Forms\Components\RichEditor
    {
        $config = static::mergeOptions(
            static::getConfig('rich'),
            $options
        );

        $component = Forms\Components\RichEditor::make($name);

        static::applyEditorConfig($component, $config);

        return $component;
    }

    public static function preset(string $preset, string $name, mixed $extra = null, array $options = []): Forms\Components\Field
    {
        $presetConfig = static::getPresetConfig($preset);

        $type = $presetConfig['type'];
        unset($presetConfig['type']);

        $baseConfig = static::getConfig($type);

        $config = static::mergeOptions($baseConfig, $presetConfig);
        $config = static::mergeOptions($config, $options);

        $component = match ($type) {
            'media_image' => Forms\Components\SpatieMediaLibraryFileUpload::make($name)
                ->collection($extra),
            'media_file' => Forms\Components\SpatieMediaLibraryFileUpload::make($name)
                ->collection($extra),
            'local_image' => Forms\Components\FileUpload::make($name)
                ->directory($extra),
            'local_file' => Forms\Components\FileUpload::make($name)
                ->directory($extra),
            'markdown' => Forms\Components\MarkdownEditor::make($name),
            'rich' => Forms\Components\RichEditor::make($name),
            default => throw new \InvalidArgumentException("Unknown form component type: {$type}"),
        };

        if (in_array($type, ['media_image', 'media_file', 'local_image', 'local_file'])) {
            static::applyUploadConfig($component, $config);
        } else {
            static::applyEditorConfig($component, $config);
        }

        return $component;
    }

    protected static function getConfig(string $type): array
    {
        $formComponents = config('sn-support.form_components', []);

        return match ($type) {
            'media_image' => static::deepMerge(
                $formComponents['upload']['common'] ?? [],
                $formComponents['upload']['media']['common'] ?? [],
                $formComponents['upload']['media']['image'] ?? [],
            ),
            'media_file' => static::deepMerge(
                $formComponents['upload']['common'] ?? [],
                $formComponents['upload']['media']['common'] ?? [],
                $formComponents['upload']['media']['file'] ?? [],
            ),
            'local_image' => static::deepMerge(
                $formComponents['upload']['common'] ?? [],
                $formComponents['upload']['local']['common'] ?? [],
                $formComponents['upload']['local']['image'] ?? [],
            ),
            'local_file' => static::deepMerge(
                $formComponents['upload']['common'] ?? [],
                $formComponents['upload']['local']['common'] ?? [],
                $formComponents['upload']['local']['file'] ?? [],
            ),
            'markdown' => static::deepMerge(
                $formComponents['editor']['common'] ?? [],
                $formComponents['editor']['markdown'] ?? [],
            ),
            'rich' => static::deepMerge(
                $formComponents['editor']['common'] ?? [],
                $formComponents['editor']['rich'] ?? [],
            ),
            default => [],
        };
    }

    protected static function getPresetConfig(string $preset): array
    {
        $presetConfig = config("sn-support.form_components.presets.{$preset}");

        if (empty($presetConfig)) {
            throw new \InvalidArgumentException("Form component preset not found: {$preset}");
        }

        if (empty($presetConfig['type'])) {
            throw new \InvalidArgumentException("Form component preset [{$preset}] missing 'type' key.");
        }

        return $presetConfig;
    }

    protected static function mergeOptions(array $defaults, array $options): array
    {
        $merged = $defaults;

        foreach ($options as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::mergeOptions($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    protected static function deepMerge(array ...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                    $result[$key] = static::deepMerge($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    protected static function applyUploadConfig(
        Forms\Components\FileUpload | Forms\Components\SpatieMediaLibraryFileUpload $component,
        array $config
    ): void {
        // if (! empty($config['disk'])) {
        //     $component->disk($config['disk']);
        // } elseif ($component instanceof Forms\Components\SpatieMediaLibraryFileUpload) {
        //     $component->disk(SupportUtils::getFilesystemDisk());
        // }

        if (isset($config['visibility'])) {
            $component->visibility($config['visibility']);
        }

        // if (! empty($config['multiple'])) {
        //     $component->multiple();
        // }

        if (! empty($config['downloadable'])) {
            $component->downloadable();
        }

        if (! empty($config['openable'])) {
            $component->openable();
        }

        if (! empty($config['reorderable'])) {
            $component->reorderable();
        }

        if (! empty($config['append_files'])) {
            $component->appendFiles();
        }

        // if (isset($config['max_files'])) {
        //     $component->maxFiles($config['max_files']);
        // }

        // if (isset($config['min_files'])) {
        //     $component->minFiles($config['min_files']);
        // }

        // if (isset($config['max_size'])) {
        //     $component->maxSize($config['max_size']);
        // }

        // if (isset($config['accepted_file_types'])) {
        //     $component->acceptedFileTypes($config['accepted_file_types']);
        // }

        if (isset($config['image_preview_height'])) {
            $component->imagePreviewHeight($config['image_preview_height']);
        }
    }

    protected static function applyEditorConfig(
        Forms\Components\MarkdownEditor | Forms\Components\RichEditor $component,
        array $config
    ): void {
        if (isset($config['toolbar_buttons'])) {
            $component->toolbarButtons($config['toolbar_buttons']);
        }

        if (isset($config['file_attachments_disk'])) {
            $component->fileAttachmentsDisk($config['file_attachments_disk']);
        }

        if (isset($config['max_length'])) {
            $component->maxLength($config['max_length']);
        }

        if (isset($config['min_length'])) {
            $component->minLength($config['min_length']);
        }

        if (! empty($config['required'])) {
            $component->required();
        }

        if (isset($config['placeholder'])) {
            $component->placeholder($config['placeholder']);
        }
    }
}
