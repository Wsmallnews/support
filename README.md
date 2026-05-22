# Wsmallnews System Base Support Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/support.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/support)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/support/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wsmallnews/support/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/support/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wsmallnews/support/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/support.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/support)

Wsmallnews System Base Support Package. Provides common models, form components, Livewire traits, plugin capabilities, enum types and other underlying capabilities for other packages.

## Features

### Base Model Layer

- **SupportModel**: Base class for all package models, with built-in `Scopeable` trait, automatically supports multi-scope isolation and multi-tenancy
- **Scopeable Trait**: Provides `scopeType()`, `scopeId()`, `scopeable()`, `snScope()` query scopes for models
- **Content Model**: Generic polymorphic content storage model, supports three content types: text, rich text, and Markdown

### Filament Form Components

| Component | Description |
| `FormComponents` | Form component factory, provides unified methods for creating file upload and editor components |

### Livewire Traits

| Trait            | Description                                                                     |
| ---------------- | ------------------------------------------------------------------------------- |
| `HasProperties`  | Generic property management, provides `getProperty()` / `setProperty()` methods |
| `HasContentType` | Content type management, supports Textarea / Richtext / Markdown                |
| `HasAuth`        | Auth user management, `getAuthUser()` / `hasAuthUser()`                         |
| `CanPagination`  | Enhanced pagination, supports scroll loading, paginator, and manual modes       |
| `CanBeContained` | Whether the component has an outer container                                    |
| `Scopeable`      | Component-level scope management for Livewire                                   |

### Plugin / Resource Traits

> Extension of bezhansalleh/filament-plugin-essentials, providing custom property management functionality

| Trait                            | Description                                                                            |
| -------------------------------- | -------------------------------------------------------------------------------------- |
| `HasCustomProperties` (Plugin)   | Plugin-level custom property management, supports multiple Resource contexts           |
| `HasCustomProperties` (Resource) | Resource-level custom property reading, supports `getCustomProperty()` shortcut method |

### Eloquent Casts

| Cast          | Description                                                                               |
| ------------- | ----------------------------------------------------------------------------------------- |
| `CounterCast` | JSON counter field transformation, supports direct access like `->like_num`, `->view_num` |
| `MoneyCast`   | Currency amount conversion, based on `moneyphp/money`                                     |
| `ImplodeCast` | Array field concatenation transformation                                                  |

### Other Features

- **Enums**: `ContentType` (textarea / richtext / markdown), `ActivityLogEvent`
- **Sms**: SMS sending functionality, based on `overtrue/easy-sms`
- **Tenant Settings**: Multi-tenant configuration storage, database Settings Repository
- **Observers**: Activity log observer, MediaLibrary observer
- **Middleware**: Multi-tenant identification middleware `IdentifyTenant`

## Installation

You can install the package via composer:

```bash
composer require wsmallnews/support:^1.0
```

Installing this package will publish the configuration files and migration files of both the third-party dependency package and the current package:

```bash
php artisan sn-support:install
```

You can publish only the config file individually:

```bash
php artisan vendor:publish --tag="sn-support-config"
```

Publish and run only the migrations individually:

```bash
php artisan vendor:publish --tag="sn-support-migrations"
php artisan migrate
```

Multi language support, you can publish the language files using:

```bash
php artisan vendor:publish --tag="sn-support-translations"
```

Publish the views (optional):

```bash
php artisan vendor:publish --tag="sn-support-views"
```

This is the contents of the published config file `config/sn-support.php`:

```php
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
                 * Text color list, effective when textColor is included in toolbar
                 */
                'text_colors' => null,
            ],
        ],
    ],
];
```

## Usage

### SupportModel Base Model

All package models should extend `SupportModel` to automatically get multi-scope and multi-tenancy capabilities:

```php
use Wsmallnews\Support\Models\SupportModel;

class YourModel extends SupportModel
{
    protected $table = 'your_table';
}
```

Automatically available query scopes:

```php
// Scope query
YourModel::scopeable('blog', 1)->get();

// Combined query (scope + tenant), will automatically get current tenant ID
YourModel::snScope('blog', 1)->get();

// Get scope information for current record
$model->getScopeType();  // 'blog'
$model->getScopeId();    // 1
$model->getScopeable();  // ['scope_type' => 'blog', 'scope_id' => 1]
```

### HasProperties Trait

Use property management in Livewire components:

```php
use Wsmallnews\Support\Livewire\Concerns\HasProperties;

class YourComponent extends Component
{
    use HasProperties;

    public function mount(): void
    {
        $this->setProperties([
            'emptyLabel' => 'No data',
            'pageSize' => 20,
        ]);
    }

    public function getEmptyLabel(): ?string
    {
        return $this->getProperty('emptyLabel', 'Default empty text');
    }
}
```

### HasCustomProperties (Plugin)

Use custom properties in Plugin:

```php
use Wsmallnews\Support\Concerns\Plugin\HasCustomProperties;

class YourPlugin implements Plugin
{
    use HasCustomProperties;

    public function register(Panel $panel): void
    {
        // Set custom properties during Panel registration
    }
}

// Usage
YourPlugin::make()
    ->customProperties([
        'pageSize' => 20,
        'enableSearch' => true,
    ]);
```

### HasCustomProperties (Resource)

Read custom properties in Resource/Page:

```php
use Wsmallnews\Support\Concerns\Resource\HasCustomProperties;

class YourPage extends Page
{
    use HasCustomProperties;

    public function getPageSize(): int
    {
        return static::getCustomProperty('pageSize', 10);
    }
}
```

### Form Components Factory

Quickly create configured form components using `FormComponents`:

```php
use Wsmallnews\Support\Filament\Forms\FormComponents;

// Media library image upload (Spatie Media Library)
FormComponents::mediaImageUpload('avatar', 'avatars');

// Media library file upload
FormComponents::mediaFileUpload('attachment', 'files');

// Local image upload
FormComponents::localImageUpload('cover');

// Local file upload
FormComponents::localFileUpload('document');

// Markdown editor
FormComponents::markdownEditor('content');

// Rich text editor
FormComponents::richEditor('description');
```

Components will automatically apply default configurations from `sn-support.php`.

### CounterCast

```php
use Wsmallnews\Support\Casts\CounterCast;

class Post extends SupportModel
{
    protected $casts = [
        'counter' => CounterCast::class,
    ];
}

// Usage
$post->counter->like_num;    // 0
$post->counter->view_num;    // 0
$post->counter->share_num;   // 0 (non-existent key defaults to 0)

// Update counter
$post->incrementJson('counter->like_num');
$post->decrementJson('counter->view_num');
```

### CanPagination

```php
use Wsmallnews\Support\Livewire\Concerns\CanPagination;

class CommentList extends Component
{
    use CanPagination;

    public string $pageType = 'scroll';     // scroll / paginator / manual
    public string $pageName = 'page';
    public int $perPage = 10;

    public function loadComments()
    {
        $query = Comment::query();
        $this->comments = $this->withPagination($query);
        // $this->pageInfo contains pagination status
        // $this->links contains pagination link HTML
    }


    public function render()
    {
        return view('comment-list', [
            'paginatorLink' => $this->links,        // paginator HTML when using paginator mode
        ]);
    }
}
```

### Multi-Tenancy Support

If your panel uses multi-tenancy, you should set the tenant model.

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Firstly, the panel where the current plugin is located should support multi tenancy before you can set this option.
    | Secondly, The tenant model should be set as a panel model.
    |
    */
    'tenant_model' => \App\Models\Team::class,
]
```

#### laravel-settings Multi-Tenancy Support

Publish the laravel-settings config file:

```bash
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="config"
```

Add the `team_database` configuration in `settings.php`, specifying the `sn_team_settings` table:

```php
return [
    /*
     * Settings will be stored and loaded from these repositories.
     */
    'repositories' => [
        'database' => [
            // ...
        ],
        'team_database' => [
            'type' => Wsmallnews\Support\Tenant\Settings\Repositories\DatabaseSettingsRepository::class,
            'model' => null,
            'table' => 'sn_team_settings',
            'connection' => null,
        ],
        // ...
    ],
]
```

The event listener will be automatically registered in the `boot` method of `SupportServiceProvider`, which initializes tenant settings in the database.

#### laravel-activitylog Multi-Tenancy Support

Publish the laravel-activitylog config file:

```bash
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

Set the `activity_model` to `\Wsmallnews\Support\Models\Activity`:

```php
return [
    // ...

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \Wsmallnews\Support\Models\Activity::class,
]
```

### media-library

Publish the laravel-medialibrary config file:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
```

Change the media observer to `\Wsmallnews\Support\Tenant\MediaLibrary\Observers\MediaObserver` in media-library, otherwise files in the filesystem will be deleted when media is deleted.

```php
return [
    /*
     * The fully qualified class name of the media observer.
     */
    'media_observer' => \Wsmallnews\Support\Tenant\MediaLibrary\Observers\MediaObserver::class,
]
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [smallnews](https://github.com/Wsmallnews)
- [bezhansalleh/filament-plugin-essentials](https://github.com/bezhansalleh/filament-plugin-essentials)
- [filament/filament](https://github.com/filamentphp/filament)
- [overtrue/easy-sms](https://github.com/overtrue/easy-sms)
- [ralphjsmit/livewire-urls](https://github.com/ralphjsmit/livewire-urls)
- [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)
- [spatie/laravel-markdown](https://github.com/spatie/laravel-markdown)
- [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary)
- [spatie/laravel-settings](https://github.com/spatie/laravel-settings)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
