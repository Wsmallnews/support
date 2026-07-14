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
    | Filament Action Components
    |--------------------------------------------------------------------------
    |
    | Unified management of default configurations for Filament action components
    |
    */
    'action_components' => [
        /**
         * Record actions 分组配置。
         * 控制 Table::recordActions() 中的操作是否包裹在 ActionGroup 下拉菜单中。
         */
        'table_record_actions' => [
            /**
             * 是否将 record actions 包裹在 ActionGroup 下拉菜单中。
             * 设为 false 时，actions 以独立按钮形式平铺展示。
             */
            'group' => true,

            /**
             * 触发器视图模式：'icon_button'（默认）、'button'、'link'。
             * - icon_button: 纯图标按钮，label 仅对屏幕阅读器可见，outlined 无效
             * - button: 完整按钮，label 可见、outlined 有效
             * - link: 链接样式，label 可见、outlined 无效
             */
            'trigger' => 'icon_button',

            /**
             * ActionGroup 触发按钮图标（Heroicon 名称，null 使用默认图标）
             */
            'icon' => null,

            /**
             * ActionGroup 触发按钮文本（null 使用 Filament 默认翻译）
             */
            'label' => null,

            /**
             * 触发按钮颜色
             */
            'color' => null,

            /**
             * 触发按钮尺寸（xs|sm|md|lg|xl）
             */
            'size' => null,

            /**
             * 是否使用 outlined 按钮样式（仅 trigger 为 button 时有效）
             */
            'outlined' => false,

            /**
             * 触发按钮的 tooltip 文本
             */
            'tooltip' => null,
        ],

        /**
         * Toolbar actions 分组配置。
         * 控制 Table::toolbarActions() 中的批量操作是否包裹在 BulkActionGroup 中。
         */
        'table_toolbar_actions' => [
            /**
             * 是否将 toolbar actions 包裹在 BulkActionGroup 中。
             * 设为 false 时，批量操作以独立按钮形式平铺展示。
             */
            'group' => true,

            /**
             * 触发器视图模式：'button'（默认）、'icon_button'、'link'。
             * BulkActionGroup 默认使用 button 模式，label 可直接显示。
             */
            'trigger' => 'button',

            /**
             * BulkActionGroup 触发按钮图标
             */
            'icon' => null,

            /**
             * BulkActionGroup 触发按钮文本
             */
            'label' => null,

            /**
             * 触发按钮颜色
             */
            'color' => null,

            /**
             * 触发按钮尺寸（xs|sm|md|lg|xl）
             */
            'size' => null,

            /**
             * 是否使用 outlined 按钮样式（仅 trigger 为 button 时有效）
             */
            'outlined' => false,

            /**
             * 触发按钮的 tooltip 文本
             */
            'tooltip' => null,
        ],
    ],

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
            /**
             * (valid for multi-file upload) Panel layout: 'compact', 'grid', 'compact circle'
             * - compact: Default layout, images in a single row
             * - grid: Grid layout, images in multiple rows
             * - compact circle: Circle avatar layout
             */
            'panel_layout' => 'grid',
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
