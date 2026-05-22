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
    | Unified management of default configurations for Filament form components
    |
    */
    'form_components' => [
        /**
         * Upload component default configuration
         */
        'upload' => [
            /**
             * Visibility
             */
            'visibility' => 'public',
            /**
             * Downloadable
             */
            'downloadable' => true,
            /**
             * Openable
             */
            'openable' => true,
            /**
             * Reorderable (valid for multi-file upload)
             */
            'reorderable' => true,
            /**
             * Append files mode (valid for multi-file upload)
             */
            'append_files' => true,
            /**
             * Maximum number of files (valid for multi-file upload)
             */
            'max_files' => 10,
            /**
             * Maximum file size, default 120MB
             */
            'max_size' => 122880,
            /**
             * Image preview height, default 200px
             */
            'image_preview_height' => '200',
        ],

        'editor' => [
            /**
             * Maximum content length, default null (unlimited)
             */
            'max_length' => null,
            /**
             * File upload configuration
             */
            'file_attachment' => [
                /**
                 * Visibility (richtext only)
                 */
                'visibility' => 'public',
                /**
                 * Maximum file size, default 120MB
                 */
                'max_size' => 122880,
            ],
            /**
             * Markdown editor configuration
             */
            'markdown' => [
                /**
                 * Toolbar buttons
                 */
                'toolbar_buttons' => [
                    ['bold', 'italic', 'strike', 'link'],
                    ['heading'],
                    ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'],
                    ['undo', 'redo'],
                ],
            ],
            /**
             * Rich text editor configuration
             */
            'richtext' => [
                /**
                 * Toolbar buttons
                 */
                'toolbar_buttons' => [
                    ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link', 'textColor'],
                    ['h2', 'h3'],
                    ['alignStart', 'alignCenter', 'alignEnd'],
                    ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'],
                    ['undo', 'redo'],
                ],
                /**
                 * Floating toolbar buttons
                 */
                'floating_toolbars' => [
                    'paragraph' => [
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'subscript',
                        'superscript',
                    ],
                    'heading' => [
                        'h1',
                        'h2',
                        'h3',
                    ],
                    'table' => [
                        'tableAddColumnBefore',
                        'tableAddColumnAfter',
                        'tableDeleteColumn',
                        'tableAddRowBefore',
                        'tableAddRowAfter',
                        'tableDeleteRow',
                        'tableMergeCells',
                        'tableSplitCell',
                        'tableToggleHeaderRow',
                        'tableToggleHeaderCell',
                        'tableDelete',
                    ],
                ],
                /**
                 * Text color list, effective when textColor is included in toolbar
                 */
                'text_colors' => null,
            ],
        ],
    ],
];
