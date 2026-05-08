<?php

use Wsmallnews\Support\Models;

return [
    /*
    |--------------------------------------------------------------------------
    | Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is a storage disk used for store files. By default, The storage disk set by filament will be used
    | You can use any disk defined in `config/firesystems.php`.
    |
    */
    'filesystem_disk' => null,

    /**
     * Custom models
     */
    'models' => [
        'content' => Models\Content::class,
        'sms_log' => Models\SmsLog::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Firstly, the panel where the current plugin is located should support multi tenancy before you can set this option.
    | Secondly, The tenant model should be set as a panel model.
    |
    */
    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Filament Form Components
    |--------------------------------------------------------------------------
    |
    | 统一管理 Filament 表单组件的默认配置，采用层级继承结构。
    | 配置合并顺序：upload.common → upload.{local|media}.common → upload.{local|media}.{image|file}
    | 编辑器合并顺序：editor.common → editor.{markdown|rich}
    |
    */
    'form_components' => [

        'upload' => [

            'common' => [
                'disk' => null,
                'visibility' => 'public',
                'downloadable' => true,
                'openable' => true,
                'reorderable' => true,
                'append_files' => true,
                'max_files' => 1,
                'min_files' => 1,
                'max_size' => 10240,
                'accepted_file_types' => ['image/*'],
                'image_preview_height' => '200',
                'uploading_message' => '上传中...',
            ],

            'local' => [
                'common' => [
                    'directory' => null,
                ],
                'image' => [
                    'image' => true,
                    'multiple' => false,
                    'uploading_message' => '图片上传中...',
                ],
                'file' => [
                    'image' => false,
                    'multiple' => false,
                    'reorderable' => false,
                    'accepted_file_types' => ['application/*', 'text/*'],
                    'max_size' => 20480,
                    'image_preview_height' => '100',
                    'uploading_message' => '文件上传中...',
                ],
            ],

            'media' => [
                'common' => [
                    'custom_properties' => null,
                ],
                'image' => [
                    'multiple' => false,
                    'uploading_message' => '图片上传中...',
                ],
                'file' => [
                    'multiple' => false,
                    'reorderable' => false,
                    'accepted_file_types' => ['application/*', 'text/*'],
                    'max_size' => 20480,
                    'image_preview_height' => '100',
                    'uploading_message' => '文件上传中...',
                ],
            ],
        ],

        'editor' => [
            'common' => [
                'required' => false,
                'placeholder' => null,
                'max_length' => null,
                'min_length' => null,
                'file_attachments_directory' => null,
                'file_attachments_disk' => null,
            ],
            'markdown' => [
                'toolbar_buttons' => [
                    'attachFiles', 'blockquote', 'bold', 'bulletList',
                    'codeBlock', 'heading', 'italic', 'link',
                    'orderedList', 'redo', 'strike', 'table', 'undo',
                ],
            ],
            'rich' => [
                'toolbar_buttons' => [
                    'attachFiles', 'blockquote', 'bold', 'bulletList',
                    'codeBlock', 'h2', 'h3', 'italic', 'link',
                    'orderedList', 'redo', 'strike', 'undo',
                ],
            ],
        ],

        'presets' => [

            'media_avatar' => [
                'type' => 'media_image',
                'max_files' => 1,
                'image_preview_height' => '100',
                'uploading_message' => '头像上传中...',
                'accepted_file_types' => ['image/jpeg', 'image/png', 'image/webp'],
            ],
            'media_cover' => [
                'type' => 'media_image',
                'max_files' => 1,
                'uploading_message' => '封面上传中...',
            ],
            'media_gallery' => [
                'type' => 'media_image',
                'multiple' => true,
                'max_files' => 20,
                'uploading_message' => '图片上传中...',
            ],
            'media_document' => [
                'type' => 'media_file',
                'uploading_message' => '文件上传中...',
            ],

            'local_avatar' => [
                'type' => 'local_image',
                'max_files' => 1,
                'image_preview_height' => '100',
                'uploading_message' => '头像上传中...',
            ],
            'local_cover' => [
                'type' => 'local_image',
                'max_files' => 1,
                'uploading_message' => '封面上传中...',
            ],
            'local_gallery' => [
                'type' => 'local_image',
                'multiple' => true,
                'max_files' => 20,
                'uploading_message' => '图片上传中...',
            ],
            'local_document' => [
                'type' => 'local_file',
                'uploading_message' => '文件上传中...',
            ],

            'simple_markdown' => [
                'type' => 'markdown',
                'toolbar_buttons' => ['bold', 'italic', 'link', 'bulletList', 'orderedList'],
            ],
            'full_markdown' => [
                'type' => 'markdown',
                'toolbar_buttons' => [
                    'attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock',
                    'heading', 'italic', 'link', 'orderedList', 'redo', 'strike',
                    'table', 'undo', 'hr', 'image', 'code',
                ],
            ],
            'simple_rich' => [
                'type' => 'rich',
                'toolbar_buttons' => ['bold', 'italic', 'link', 'bulletList', 'orderedList'],
            ],
            'full_rich' => [
                'type' => 'rich',
                'toolbar_buttons' => [
                    'attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock',
                    'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'undo',
                ],
            ],
        ],
    ],

];
