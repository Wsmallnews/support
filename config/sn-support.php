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

    /** Custom models */
    'models' => [
        'content' => Models\Content::class,
        'sms_log' => Models\SmsLog::class,
        'scheduled_task' => Models\ScheduledTask::class,
        'composition' => Models\Composition::class,
        'page' => Models\Page::class,
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
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Site default currency (ISO 4217) for money display, MoneyCast fallback
    | and money package defaults. If the consumer app sets config('app.currency'),
    | that value takes priority over this one.
    |
    */

    'currency' => 'CNY',

    /*
    |--------------------------------------------------------------------------
    | Filament Action Components
    |--------------------------------------------------------------------------
    |
    | Unified management of default configurations for Filament action components
    |
    */
    'action_components' => [
        /** Record actions grouping (Table::recordActions()). */
        'table_record_actions' => [
            /** Wrap actions in an ActionGroup; false renders one button per action. */
            'group' => true,

            /** Trigger style: icon_button (default) hides the label, button shows it, link renders as a link. */
            'trigger' => 'icon_button',

            'icon' => null,

            'label' => null,

            'color' => null,

            'size' => null,

            'outlined' => false,

            'tooltip' => null,
        ],

        /** Bulk actions grouping (Table::toolbarActions()). */
        'table_toolbar_actions' => [
            /** Wrap bulk actions in a BulkActionGroup; false renders one button per action. */
            'group' => true,

            /** Trigger style: button (default), icon_button or link. */
            'trigger' => 'button',

            'icon' => null,

            'label' => null,

            'color' => null,

            'size' => null,

            'outlined' => false,

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
        /** Upload component default configuration */
        'upload' => [
            /** Visibility */
            'visibility' => 'public',
            /** Downloadable */
            'downloadable' => true,
            /** Openable */
            'openable' => true,
            /** Reorderable (valid for multi-file upload) */
            'reorderable' => true,
            /** Append files mode (valid for multi-file upload) */
            'append_files' => true,
            /** Maximum number of files (valid for multi-file upload) */
            'max_files' => 10,
            /** Maximum file size, default 120MB */
            'max_size' => 122880,
            /** Image preview height, default 200px */
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
            /** Maximum content length, default null (unlimited) */
            'max_length' => null,
            /** File upload configuration */
            'file_attachment' => [
                /** Visibility (richtext only) */
                'visibility' => 'public',
                /** Maximum file size, default 120MB */
                'max_size' => 122880,
            ],
            /** Markdown editor configuration */
            'markdown' => [
                /** Toolbar buttons */
                'toolbar_buttons' => [
                    ['bold', 'italic', 'strike', 'link'],
                    ['heading'],
                    ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'],
                    ['undo', 'redo'],
                ],
            ],
            /** Rich text editor configuration */
            'richtext' => [
                /** Toolbar buttons */
                'toolbar_buttons' => [
                    ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link', 'textColor'],
                    ['h2', 'h3'],
                    ['alignStart', 'alignCenter', 'alignEnd'],
                    ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                    ['table', 'attachFiles'],
                    ['undo', 'redo'],
                ],
                /** Floating toolbar buttons */
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
                /** Text color list, effective when textColor is included in toolbar */
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

            /** Default content type */
            'default_type' => ContentType::Richtext,
        ],
    ],

        /*
        |--------------------------------------------------------------------------
        | Theme (Design Tokens)
        |--------------------------------------------------------------------------
        |
        | Runtime overrides for --sn-* tokens; the layout renders them as a style block.
        | Keys drop the --sn- prefix; values are CSS lengths; _lg keys only affect the lg breakpoint.
        |
        */
    'theme' => [
        'radius_card' => null,
        'radius_card_lg' => null,
        'radius_control' => null,
        'space_page' => null,
        'space_page_lg' => null,
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
        | Global defaults for the search registry. Modules may override any key in their
        | own config section; any key here can be redeclared per module.
        |
        */
    'search' => [
        'engine' => 'database',

        /** Frontend mode: dropdown (results in a floating panel) or page (dedicated results page). */
        'display' => 'dropdown',

        /**
        * Results page URL when display is page: a string gets ?q= appended, a closure
        * receives the query and returns the full URL.
        */
        'page' => null,

        /** Results per source. */
        'results_limit' => 8,

        /** Split the query on whitespace; terms are OR-ed across fields. */
        'split_terms' => true,

        /** How split terms combine: and (all must match, default) or or (any match). */
        'terms_operator' => 'and',

        /** Render a submit button inside the field (display = page only). */
        'show_search_button' => false,

        /** Case-insensitive LIKE matching. */
        'case_insensitive' => true,

        /** Frontend input debounce. */
        'debounce' => '300ms',
    ],

        /*
        |--------------------------------------------------------------------------
        | Sitemap
        |--------------------------------------------------------------------------
        |
        | Endpoints and caching for SitemapRegistry; cache_ttl null or 0 disables caching.
        |
        */
    'sitemap' => [
        /** Register the /sitemap.xml and /robots.txt routes. */
        'enabled' => true,

        /** Endpoint paths, registered at the root and available on every bound domain. */
        'sitemap_uri' => 'sitemap.xml',
        'robots_uri' => 'robots.txt',

        /** Seconds the rendered sitemap is cached; call Sitemap::flush() after content changes. */
        'cache_ttl' => 3600,

        /** Hide modules whose declared domain does not match the request host. */
        'domain_filter' => true,
    ],

        /*
        |--------------------------------------------------------------------------
        | Feeds (RSS)
        |--------------------------------------------------------------------------
        |
        | Named RSS streams registered per module, unlike the single site-wide sitemap.
        |
        */
    'feeds' => [
        /** Register the /feed and /feed/{name} routes. */
        'enabled' => true,

        /** Path segment used by the root endpoints and by Feed::routes() inside a module group. */
        'uri' => 'feed',

        /** Entry cap for the merged /feed stream; named streams declare their own limit. */
        'limit' => 50,

        /** Seconds each rendered stream is cached; call Feed::flush() after content changes. */
        'cache_ttl' => 3600,

        /** Hide modules whose declared domain does not match the request host. */
        'domain_filter' => true,
    ],

        /*
        |--------------------------------------------------------------------------
        | Scheduler
        |--------------------------------------------------------------------------
        |
        | Drives the sn-support:run-scheduled-tasks command and its frequency.
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

        /** Batch size */
        'batch_size' => 100,

        /** Enable without overlapping tasks */
        'without_overlapping' => true,

        /** Overlapping expire minutes (minutes) */
        'overlapping_expire_minutes' => 5,
    ],
];
