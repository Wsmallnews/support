<?php

use Wsmallnews\Support\Enums\ContentType;
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
        'scheduled_task' => Models\ScheduledTask::class,
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

        /**
         * Content type group default configuration (FormComponents::contentTypeGroup)
         * Used as fallback when the caller does not pass types / default_type
         */
        'content' => [
            /**
             * Allowed content types, null = all types
             *
             * @var array<int, ContentType>|null
             */
            'types' => null,

            /**
             * Default content type
             */
            'default_type' => ContentType::Richtext,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme (Design Tokens)
    |--------------------------------------------------------------------------
    |
    | sn-* 设计令牌的运行时覆盖：无需重新构建 CSS，配置后由布局自动渲染 <style> 块。
    | 键 = 令牌名（去掉 --sn- 前缀），值为 CSS 长度（如 "1rem"、"14px"）；null = 不覆盖。
    | 带 _lg 后缀的键只覆盖桌面档（>= lg / 64rem），与响应式默认值的结构一一对应。
    |
    */
    'theme' => [
        'radius_card' => null,
        'radius_card_lg' => null,
        'radius_control' => null,
        'space_page' => null,
        'space_page_lg' => null,
        'space_page_y' => null,
        'space_page_y_lg' => null,
        'space_page_x' => null,
        'space_page_x_lg' => null,
        'space_card' => null,
        'space_card_lg' => null,
        'space_row' => null,
        'space_row_lg' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | 通用全局搜索配置。engine 为全局兜底引擎：'database'（WHERE LIKE，默认）
    | 'scout'（需安装 laravel/scout）或引擎实现类名；各包注册搜索时可用
    | 搜索级配置覆盖。split_terms 控制是否按空白拆词（多词 AND、字段间 OR）。
    |
    */
    'search' => [
        'engine' => 'database',

        /**
         * 前端搜索结果的展示方式：
         * - 'dropdown'：输入即搜，结果浮层展示在搜索框下方，点击条目跳转（默认）
         * - 'page'：搜索框回车后跳转到独立的搜索结果页
         * 各扩展包可在自己的配置节（如 sn-cms.search.display）覆盖此值
         */
        'display' => 'dropdown',

        /**
         * 搜索结果页地址（display = page 时的全局兜底）：
         * - URL 字符串：回车跳转时由 support 统一拼接 ?q=关键词
         * - 匿名函数：fn (?string $query) => ... 接收搜索关键词，自行返回完整 URL（含 ?q= 等参数）
         * 模块级通过 Search::config($search, ['page' => ...]) 声明覆盖
         * （在扩展包 ServiceProvider 中注册，与来源 url 选项同风格）
         */
        'page' => null,

        /**
         * 每个来源默认返回条数
         */
        'results_limit' => 8,

        /**
         * 是否按空白拆词（多词 AND、字段间 OR）
         */
        'split_terms' => true,

        /**
         * LIKE 搜索是否大小写不敏感
         */
        'case_insensitive' => true,

        /**
         * 前端搜索组件的防抖时长
         */
        'debounce' => '300ms',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    |
    | 定时调度任务的配置。control 决定是否启用定时扫描任务，
    | frequency 控制 sn-support:run-scheduled-tasks 命令的执行频率。
    | 频率格式参考 Laravel Schedule：everyMinute / everyFiveMinutes / dailyAt:13:00 等。
    |
    */
    'scheduler' => [
        'enabled' => true,

        /**
         * Auto-audit comments frequency
         * Support all Laravel schedule frequency methods, like:
         * 'everyMinute', 'everyFiveMinutes', 'everyTenMinutes',
         * 'everyThirtyMinutes', 'hourly', 'daily', 'weekly'
         *
         * Parameter format:
         * dailyAt:13:00 => dailyAt('13:00') | monthlyOn:4,15:00 => monthlyOn(4, '15:00')
         */
        'frequency' => 'everyMinute',

        /**
         * Batch size
         */
        'batch_size' => 100,

        /**
         * Enable without overlapping tasks
         */
        'without_overlapping' => true,

        /**
         * Overlapping expire minutes (minutes)
         */
        'overlapping_expire_minutes' => 5,
    ],
];
