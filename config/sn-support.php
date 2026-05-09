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
    | 统一管理 Filament 表单组件的默认配置
    |
    */
    'form_components' => [
        /**
         * 上传组件默认配置
         */
        'upload' => [
            /**
             * 可见性
             */
            'visibility' => 'public',
            /**
             * 可下载
             */
            'downloadable' => true,
            /**
             * 可打开
             */
            'openable' => true,
            /**
             * 可排序 (多文件上传有效)
             */
            'reorderable' => true,
            /**
             * 追加文件模式 (多文件上传有效)
             */
            'append_files' => true,
            /**
             * 最大文件数 (多文件上传有效)
             */
            'max_files' => 10,
            /**
             * 最大文件大小 默认 120MB
             */
            'max_size' => 122880,
            /**
             * 图片预览高度, 默认 200px
             */
            'image_preview_height' => '200',
        ],

        'editor' => [
            /**
             * 内容字符最大长度，默认 null 不限制
             */
            'max_length' => null,
            /**
             * 文件上传配置
             */
            'file_attachment' => [
                /**
                 * 可见性 (仅 richtext 有效)
                 */
                'visibility' => 'public',
                /**
                 * 最大文件大小 默认 120MB
                 */
                'max_size' => 122880,
            ],
            /**
             * markdown 编辑器配置
             */
            'markdown' => [
                /**
                 * 工具栏按钮
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
             * 富文本编辑器配置
             */
            'richtext' => [
                /**
                 * 工具栏按钮
                 */
                'toolbar_buttons' => [
                    ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link', 'textColor'],
                    ['h2', 'h3'],
                    ['alignStart', 'alignCenter', 'alignEnd'],
                    ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'], // The `customBlocks` and `mergeTags` tools are also added here if those features are used.
                    ['undo', 'redo'],
                ],
                /**
                 * 浮动工具栏按钮
                 */
                'floating_toolbars' => [
                    'paragraph' => [
                        'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript',
                    ],
                    'heading' => [
                        'h1', 'h2', 'h3',
                    ],
                    'table' => [
                        'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                        'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                        'tableMergeCells', 'tableSplitCell',
                        'tableToggleHeaderRow', 'tableToggleHeaderCell',
                        'tableDelete',
                    ],
                ],
                /**
                 * 文本颜色列表, 工具栏中包含 textColor 时生效
                 */
                'text_colors' => null,
            ],
        ],
    ],
];
