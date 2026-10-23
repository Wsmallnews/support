# Wsmallnews System Base Support Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/support.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/support)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/support/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wsmallnews/support/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/support/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wsmallnews/support/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/support.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/support)

Base package for the Wsmallnews Filament ecosystem: shared models, component factories, Livewire traits, registries (search, sitemap, feeds, SEO, composition, scheduled tasks, modules), money handling, and the `sn-*` frontend CSS layer.

Every other Wsmallnews package builds on this one. Package-specific conventions for AI agents live in [`resources/boost/guidelines/core.blade.php`](resources/boost/guidelines/core.blade.php).

## Requirements

- PHP 8.2+
- Filament 4 or 5 (see `composer.json`)

## Installation

```bash
composer require wsmallnews/support:^1.0
php artisan sn-support:install
```

`sn-support:install` publishes this package's config and migrations, publishes the third-party resources it manages (media-library, laravel-settings, laravel-activitylog), then offers to run `migrate`. Add `--no-deps` to publish only this package.

Individual publish tags:

```bash
php artisan vendor:publish --tag="sn-support-config"
php artisan vendor:publish --tag="sn-support-migrations"
php artisan vendor:publish --tag="sn-support-translations"
php artisan vendor:publish --tag="sn-support-views"
```

## Configuration

All keys live in `config/sn-support.php`:

| Key | Default | Purpose |
|---|---|---|
| `filesystem_disk` | `null` | Disk for uploads; `null` falls back to Filament's default disk |
| `models.content` | `Models\Content::class` | Swappable model class |
| `models.sms_log` | `Models\SmsLog::class` | Swappable model class |
| `models.scheduled_task` | `Models\ScheduledTask::class` | Swappable model class |
| `models.composition` | `Models\Composition::class` | Swappable model class |
| `models.page` | `Models\Page::class` | Swappable model class |
| `tenant_model` | `null` | Tenant model; `null` disables tenancy |
| `currency` | `CNY` | Site currency (ISO 4217), see [Money](#money) |
| `action_components.table_record_actions.group` | `true` | Wrap record actions in an `ActionGroup` |
| `action_components.table_record_actions.trigger` | `icon_button` | `icon_button`, `button` or `link` |
| `action_components.table_record_actions.{icon,label,color,size,outlined,tooltip}` | `null` / `false` | Trigger appearance |
| `action_components.table_toolbar_actions.group` | `true` | Wrap bulk actions in a `BulkActionGroup` |
| `action_components.table_toolbar_actions.trigger` | `button` | `button`, `icon_button` or `link` |
| `action_components.table_toolbar_actions.{icon,label,color,size,outlined,tooltip}` | `null` / `false` | Trigger appearance |
| `form_components.upload.*` | see file | `visibility`, `downloadable`, `openable`, `reorderable`, `append_files`, `max_files`, `max_size`, `image_preview_height`, `panel_layout` |
| `form_components.editor.max_length` | `null` | Editor character limit |
| `form_components.editor.file_attachment.*` | see file | `visibility`, `max_size` |
| `form_components.editor.markdown.toolbar_buttons` | see file | Markdown toolbar layout |
| `form_components.editor.richtext.{toolbar_buttons,floating_toolbars,text_colors}` | see file | Rich text toolbar layout |
| `form_components.content.types` | `null` | Allowed content types; `null` means all |
| `form_components.content.default_type` | `ContentType::Richtext` | Default content type |
| `theme.*` | `null` | Runtime overrides for `--sn-*` design tokens |
| `search.*` | see [Search](#search) | Global search defaults |
| `sitemap.*` | see [Sitemap](#sitemap) | Sitemap and robots endpoints |
| `feeds.*` | see [Feed](#feed) | RSS endpoints |
| `scheduler.*` | see [Scheduled tasks](#scheduled-tasks) | Task runner settings |

## Features

### Base model layer

- **SupportModel** — base class for every package model, with `Scopeable` and tenant awareness.
- **Scopeable** — `scopeType()`, `scopeId()`, `scopeable()`, `snScope()` query scopes.
- **Content** — polymorphic content storage for `textarea`, `richtext`, `markdown` and `images`.
- **Composition**, **Page**, **Tag**, **ScheduledTask**, **SmsLog**, **Activity** models.

### Filament component factories

| Factory | Description |
|---|---|
| `FormComponents` | Upload, editor, content type group, enum toggles and order column inputs |
| `FilterComponents` | Status, morph and date range filters |
| `ActionComponents` | Delete, edit, toggle, switch and error-isolating bulk actions |
| `ColumnComponents` | Content, model, relation and morph table columns |

Factories apply `config('sn-support.*')` defaults. When you use a factory, do not set disk, visibility or similar defaults by hand.

### Livewire traits

| Trait | Description |
|---|---|
| `HasProperties` | `getProperty()` / `setProperty()` over a property bag |
| `HasContentType` | Content type property (textarea, richtext, markdown, images) |
| `HasAuth` | Auth user management, `authUser()` / `hasAuthUser()` |
| `CanPagination` | Scroll, paginator and manual modes; includes `WithPagination` |
| `CanBeContained` | Whether the component renders its own container |
| `Scopeable` | `#[Locked]` scope type and id |
| `HasModuleContext` | Config lookup for a component reused by another module |

### Eloquent casts

| Cast | Description |
|---|---|
| `CounterCast` | JSON counters with `0` defaults for missing keys |
| `MoneyCast` | Integer minor units to `Cknow\Money\Money` |
| `ImplodeCast` | Comma separated string to array |

### Other capabilities

- **Enums** — `ContentType`, `CompositionStatus`, `PageStatus`, `ScheduledTaskStatus`, `ActivityLogEvent`.
- **SMS** — `Features\Sms` on top of `overtrue/easy-sms`.
- **Tenant settings** — a database settings repository for `spatie/laravel-settings`.
- **Observers** — activity log and media library observers.
- **Middleware** — `IdentifyTenant` and `InitializeSeo`.

## Usage

### SupportModel

```php
use Wsmallnews\Support\Models\SupportModel;

class Post extends SupportModel
{
    protected $table = 'posts';
}

Post::scopeable('blog', 1)->get();   // scope filter
Post::snScope('blog', 1)->get();     // scope plus current tenant

$post->getScopeType();               // 'blog'
$post->getScopeId();                 // 1
$post->getScopeable();               // ['scope_type' => 'blog', 'scope_id' => 1]
```

`scope_id` may be `0`, which means "every record of this scope type". Because `0` is a real value, queries must use `scopeScopeId(0)` (internally `whereIn`) rather than `where('scope_id', 0)`.

### Component factories

```php
use Wsmallnews\Support\Filament\Forms\FormComponents;

FormComponents::mediaImageUpload('avatar', 'avatars');   // Spatie Media Library
FormComponents::mediaFileUpload('attachment', 'files');
FormComponents::plainImageUpload('cover');               // stored on the disk, path in the column
FormComponents::plainFileUpload('document');
FormComponents::markdownEditor('description');
FormComponents::richEditor('content');
FormComponents::enumsToggleButtons(PostStatus::class, 'status');
FormComponents::orderColumnInput();
FormComponents::contentTypeGroup(types: null, defaultType: ContentType::Richtext);
```

```php
use Wsmallnews\Support\Filament\Filters\FilterComponents;
use Wsmallnews\Support\Filament\Actions\ActionComponents;
use Wsmallnews\Support\Filament\Tables\ColumnComponents;

FilterComponents::statusFilter(PostStatus::class);
FilterComponents::createUpdateRangeFilter();
FilterComponents::dateTimeRangeFilter('published_at', 'Published');

ActionComponents::toggleAction(PostStatus::class, 'status');    // enum with exactly two cases
ActionComponents::switchAction(PostStatus::class, 'status');    // enum with more than two cases
ActionComponents::bulkAction('bulk_publish', fn ($action, $record) => $record->publish());

ColumnComponents::contentColumn('content', 'Content');
ColumnComponents::morphColumn('causer_type', 'Causer', fn ($r) => $r->causer, fn ($r) => $r->causer_type, fn ($r) => $r->causer_id);
```

`bulkAction()` wraps the per-record closure in its own try/catch, counts failures and only reports the first exception. Pass a `prepare` closure for batch preflight work such as eager loading; a throw there fails the whole batch.

### Livewire

```php
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\CanPagination;
use Wsmallnews\Support\Livewire\Concerns\HasProperties;

class CommentList extends Base
{
    use CanPagination;
    use HasProperties;

    public string $pageType = 'scroll';   // scroll, paginator or manual
    public int $perPage = 10;

    public function loadComments(): void
    {
        $this->comments = $this->withPagination(
            Comment::query(),
            fingerprint: implode('|', [$this->search, $this->sort]),
        );
    }
}
```

`Base::transaction()` respects `Halt`:

```php
$this->transaction(function (): void {
    // ...
});

$this->halt();   // stop and roll back
```

`withPagination()` caches by page and fingerprint, so pass a fingerprint that covers every condition affecting the result set. Call `resetPagination()` after data changes.

### Scopeable

```php
use Wsmallnews\Support\Data\ScopeableContext;

$context = new ScopeableContext('post', 0);
$context->isGlobal();                                            // true

ScopeableContext::fromArray(['scope_type' => 'store', 'scope_id' => 5]);
scopeable_context(['scope_type' => 'post', 'scope_id' => 0]);
scopeable_query(Post::query(), ['scope_type' => 'post', 'scope_id' => 0]);
```

Filament side:

- `Filament\Concerns\HasScopeableProperties` — static `scopeType()` / `scopeId()`.
- `Filament\Resources\Concerns\Scopeable` — `applyScopeableToQuery()`.
- `Filament\Pages\Concerns\Scopeable` — page-level scope helpers.

A misconfigured scope throws `InvalidScopeException` instead of falling back to a `default` instance.

### Configuration mode for resources and pages

```php
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class PostResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;
}
```

```php
use Wsmallnews\Support\Filament\Concerns\RegistersConfigurable;

class CmsPlugin implements Plugin
{
    use RegistersConfigurable;

    public function register(Panel $panel): void
    {
        $this->registerConfigurableResources($panel);
        $this->registerConfigurablePages($panel);
    }
}
```

`module_id` is injected by the registering plugin, so cross-module registration needs no manual declaration.

### Exceptions

Package exceptions extend `SupportException`, so callers can catch the whole family:

```php
use Wsmallnews\Support\Exceptions\SupportException;

class CmsException extends SupportException {}
```

`InvalidScopeException` signals a broken scope configuration.

### Enum helper

```php
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum PostStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Draft = 'draft';
    case Published = 'published';
}
```

### Casts

```php
use Wsmallnews\Support\Casts\CounterCast;
use Wsmallnews\Support\Casts\ImplodeCast;
use Wsmallnews\Support\Casts\MoneyCast;

protected $casts = [
    'counter' => CounterCast::class,               // missing keys read as 0
    'tags' => ImplodeCast::class,                  // comma separated string <-> array
    'price' => MoneyCast::class . ':currency',     // binds the row's currency column
];

$post->counter->like_num;                          // 0
$post->incrementJson('counter->like_num');
```

### Rocket

`Rocket` is a three-stage pipeline payload: `params` (read-only input), `radars` (mutable intermediates) and `payloads` (final results).

```php
$rocket = new Rocket;
$rocket->setParams(['user_id' => 1]);
$rocket->setRadar('key', 'value');
$rocket->setPayloads(['result' => $data]);

$rocket->getParam('user_id');
$rocket->getRadar('key');
$rocket->getPayloads();   // Collection
```

Never serialise a whole `Rocket`: radars may hold Eloquent models. Pass scalars such as an id through queues instead.

## Feature registries

All registries key their entries by **module id** (the plugin id, e.g. `sn-cms`). Register in `packageBooted()`.

### Modules

`ModuleRegistry` is the single source of truth for module identity. Register once per package:

```php
use Wsmallnews\Support\Features\Modules\Module;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;

ModuleRegistry::register(new Module(
    id: 'sn-cms',
    namespace: 'Wsmallnews\\Cms',
    plugin: CmsPlugin::class,
));
```

- `moduleOf($class)` resolves the owning module by longest namespace prefix. `HasModuleContext` uses it as the default, so components need no manual owner.
- `require($id)` throws when a module is missing, which catches typos in registries at boot instead of silently dropping registrations.
- `ModuleRegistry::flush()` resets the registry in tests.

### Search

`SearchRegistry` (facade `Search`) collects searchable sources per module.

```php
use Wsmallnews\Support\Facades\Search;

Search::config('sn-cms', [
    'display' => 'page',
    'page' => fn (?string $query) => route('cms.search', ['q' => $query]),
])->registers('sn-cms', [
    [
        'key' => 'post',
        'model' => Post::class,
        'group' => 'Posts',
        'query' => fn ($query) => $query->published(),
        'scopeable' => ['scope_type' => 'sn-cms', 'scope_id' => 0],
        'url' => fn ($record) => route('cms.posts.show', $record),
    ],
]);

Search::search(null, 'keyword');      // every registered module, grouped by label
Search::search('sn-cms', 'keyword');  // one module; an unknown module throws
```

Source options:

| Option | Description |
|---|---|
| `key` | Source identifier; defaults to the morph alias or class basename |
| `model` | Model class or morph alias (**required**) |
| `group` | Group label; defaults to the model label |
| `fields` | LIKE columns; defaults to the model's keyword fields minus dotted relation paths |
| `limit` | Results per source |
| `sort` | Source order, ascending |
| `query` | LIKE engine query modifier |
| `scout` | Scout index modifier; overrides `query`, `scopeable` and `fields` |
| `scopeable` | Scope filter for the LIKE engine |
| `title` / `description` / `cover` / `badge` | Display resolvers; default to `HasSnSubject` data |
| `url` | Link resolver; **only** the registrant can provide it, otherwise items are unlinked |
| `view` | Custom item view; falls back to `SearchSource::DEFAULT_ITEM_VIEW` |
| `render` | `fn ($result, $query)` renderer, wins over `view` |
| `visible` | Closure deciding whether the source takes part |
| `results` | Fully custom results, bypassing the engine |

Module options (`Search::config()`) accept **any** key from `sn-support.search`. Resolution order everywhere is component property → module declaration → global config. Passing `null` restores the global fallback. Re-registering a key overrides the previous source, which lets an app replace a package source.

Engines: `database` (default, `WHERE ... LIKE`), `scout` (requires `laravel/scout` and `Searchable` on the model), or a class implementing `Engines\Engine`. Engines implementing `ConfigurableEngine` receive the module options.

Search config keys:

| Key | Default | Purpose |
|---|---|---|
| `engine` | `database` | Fallback engine |
| `display` | `dropdown` | `dropdown` or `page` |
| `page` | `null` | Results page: string gets `?q=` appended, closure builds the URL |
| `results_limit` | `8` | Results per source |
| `split_terms` | `true` | Split the query on whitespace |
| `terms_operator` | `and` | `and` or `or` for the split terms |
| `show_search_button` | `false` | Render a submit button (page mode only) |
| `case_insensitive` | `true` | Case-insensitive LIKE |
| `debounce` | `300ms` | Frontend input debounce |

Components:

```blade
<livewire:sn-support::components.search :module="'sn-cms'" placeholder="Search…" :limit="5" />
<livewire:sn-support::components.search-results module="sn-cms" :limit="10" />
```

`components.search` renders a dropdown or a search field; `components.search-results` renders the result area of a dedicated page and keeps `?q=` in sync.

### Sitemap

`SitemapRegistry` (facade `Sitemap`) aggregates URL sources and robots rules. support exposes `/sitemap.xml` and `/robots.txt` at the root, so they work on every bound domain.

```php
use Wsmallnews\Support\Facades\Sitemap;

Sitemap::config('sn-cms', ['domain' => 'cms.example.com'])
    ->registers('sn-cms', [
        [
            'key' => 'posts',
            'urls' => fn () => Post::query()->published()->get()->map(fn ($post) => [
                'loc' => route('cms.posts.show', $post),
                'lastmod' => $post->updated_at,
            ]),
        ],
    ])
    ->robots('sn-cms', ['disallow' => ['cms/search']]);
```

- `robots.txt` always contains the Filament panel paths, the registered disallow rules and a `Sitemap:` line for the current host.
- Modules declaring a `domain` that does not match the request are skipped, including their robots rules. Route patterns such as `{tenant:slug}.example.com` are supported.
- The rendered sitemap is cached as a whole; call `Sitemap::flush()` after content changes.

Config keys: `enabled`, `sitemap_uri`, `robots_uri`, `cache_ttl` (`null` or `0` disables), `domain_filter`.

### Feed

`FeedRegistry` (facade `Feed`) publishes RSS 2.0 streams. Unlike the sitemap, a site can expose several named streams, so readers subscribe to what they care about.

```php
use Wsmallnews\Support\Facades\Feed;

Feed::config('sn-cms', ['domain' => 'cms.example.com'])
    ->register('sn-cms', 'posts', [
        'title' => fn () => config('app.name') . ' - Posts',
        'label' => fn () => __('Posts'),
        'description' => fn () => null,
        'link' => fn () => url('/'),
        'limit' => 50,
        'items' => fn () => Post::query()->published()->get()->map(fn ($post) => [
            'title' => $post->title,
            'url' => route('cms.posts.show', $post),
            'description' => $post->description,
            'updated_at' => $post->published_at,
        ]),
    ]);
```

- `items` is required and must be a closure; every entry needs a `url`.
- Feed names are URL segments and must be **globally unique**; duplicates throw at registration.
- `/feed` merges every visible stream, newest first, truncated by `feeds.limit`; `/feed/{name}` serves one stream.
- Register module-scoped endpoints inside the module route group with `Feed::routes('sn-cms')`, which adds `{prefix}/feed` and `{prefix}/feed/{name}`. Call it before catch-all segments such as `navigation/{slug}`.
- `Feed::autodiscoveryTags($module)` returns the `<link rel="alternate">` tags for the layout head.
- Call `Feed::flush()` after content changes.

Config keys: `enabled`, `uri`, `limit`, `cache_ttl`, `domain_filter`.

### Seo

`Seo` holds per-module site defaults and the current page context.

```php
use Wsmallnews\Support\Facades\Seo;

// Site defaults, once per module in packageBooted()
Seo::config('sn-cms', [
    'site_name' => fn () => config('app.name'),
    'description' => fn () => null,
    'image' => fn () => null,
    'favicon' => fn () => '/favicon.ico',
    'analytics_code' => fn () => null,
]);

// Per page, in the routed page
Seo::init('sn-cms')
    ->title($post->title)
    ->description($post->description)
    ->article(['datePublished' => $post->published_at?->toIso8601String()]);
```

Render in the layout: `@snSeo` in `<head>`, `@snSeoAnalytics` before `</body>`, `@snFeeds('sn-cms')` for autodiscovery links.

- `init()` runs in the `seo-init:{module}` route middleware on first render only. Livewire update requests do not re-render the layout, so they need no re-initialisation.
- `article()` assembles an Article JSON-LD node from the page data and switches `og:type` to `article`; `website()` emits the WebSite node; `jsonLd()` appends raw nodes.
- Relative image and canonical URLs are made absolute at render time.

### Composition

`CompositionRegistry` stores the component types a module can compose, plus optional purpose slots (for example a detail-page sidebar).

```php
use Wsmallnews\Support\Facades\CompositionRegistry;

CompositionRegistry::registerPurposes('sn-cms', [
    'post-sidebar' => [
        'label' => fn () => __('Post sidebar'),
        'positions' => ['left', 'right'],
    ],
])->registers('sn-cms', [
    [
        'type' => 'post-detail',
        'label' => fn () => __('Post detail'),
        'component' => \Wsmallnews\Cms\Livewire\PostDetail::class,
        'forms' => fn () => [/* Filament fields */],
        'provides' => fn (array $extras) => ['post' => $extras['post'] ?? null],
    ],
]);
```

- `component` / `components` map one type to one or more Livewire components; `forms` returns the admin form.
- `provides` seeds the row context for later components; `context` declares which keys an item consumes. Context flows left to right inside a row and never crosses rows.
- Rows resolve through `CompositionRenderer::resolveRows()`; `resolveForPurpose()` returns `null` when nothing matches so callers can fall back to their default layout.

### Money

Store money as **integer minor units** in the database, never as floats.

- `sn_money()` returns the `MoneyManager`: `money()`, `minor()`, `decimal()`, `add()`, `subtract()`, `multiply()`, `sum()`, `allocate()`, `format()`, `formatParts()`, `symbol()`, `jsonFields()`, `jsonSum()`, `jsonFormat()`.
- Scalar input is read as **decimal major units**; `int` input is read as **minor units**. Never pass a float result of your own arithmetic — go through the manager.
- Split amounts with `allocate()`; it distributes the remainder so the total is preserved.
- Currency resolution: `config('app.currency')` → `sn-support.currency` → `CNY`.
- `MoneyCast::class . ':currency'` binds a per-row currency column; plain `MoneyCast::class` always uses the site currency.

```php
sn_money()->format(1999);                              // ￥19.99
sn_money()->minor('19.99');                            // 1999
sn_money()->allocate(10000, [1, 1, 1]);                // [3334, 3333, 3333]
sn_money()->jsonSum(['shipping' => 500, 'tax' => 120]);
```

`Features\Currency` and `sn_currency()` are deprecated; `MoneyManager` replaces them.

### Scheduled tasks

`ScheduledTaskRegistry` (facade `ScheduledTask`) maps a morph type to the actions that can be scheduled for it.

```php
use Wsmallnews\Support\Facades\ScheduledTask;

ScheduledTask::registers('sn_post', [
    'publish' => ['label' => __('Publish'), 'handler' => PublishHandler::class],
    'unpublish' => ['label' => __('Unpublish'), 'handler' => UnpublishHandler::class],
]);

// In a form
ScheduledTask::scheduleRepeater('sn_post');
```

Action options: `action`, `label`, `forms` (extra payload fields), `handler` (a closure receiving the task and payload), `visible` (closure receiving the repeater `Get`, used for mutually exclusive states). Actions without `forms` hide the payload fieldset.

The repeater only loads `pending` tasks; executed, cancelled and failed rows stay in the table for auditing.

Run due tasks with:

```bash
php artisan sn-support:run-scheduled-tasks
```

Schedule that command in `routes/console.php` or the console kernel. Config keys: `scheduler.enabled`, `scheduler.frequency` (`everyMinute`, `everyFiveMinutes`, `dailyAt:13:00`, ...), `scheduler.batch_size`, `scheduler.without_overlapping`, `scheduler.overlapping_expire_minutes`.

Filament resources:

- `Filament\Resources\ScheduledTasks\BaseResource` — list and view pages, table and infolist.
- `ScheduledTasks\Widgets\ScheduledTasks` — footer or header widget for a detail page.
- `ScheduledTasks\Concerns\ViewScheduledTasksAction` — row action opening the record's tasks in a slide-over.

### Activity logs and tags

- `Filament\Resources\ActivityLogs\BaseResource` — list, view, export and timeline. `getEloquentQuery()` filters by the default `log_name`. Models may implement `Contracts\HasModelLabel` to customise their label in the type filter.
- `Filament\Resources\Tags\BaseResource` — extend it and override `getTagType()` to scope a tag resource to one tag type.

### Multi-tenancy

Set `sn-support.tenant_model` to enable. `IdentifyTenant` is registered as a global Livewire persistent middleware and resolves the tenant from the route's `tenant` parameter by `slug`.

```php
has_tenancy();               // whether a tenant is active
current_tenant();            // panel or frontend tenant model
frontend_has_tenancy();
frontend_current_tenant();
is_in_panel();
sn_route('page.show');       // adds the tenant parameter when needed
```

Tenant settings use `Tenant\Settings\Repositories\DatabaseSettingsRepository` with the `sn_team_settings` table. Register it in `config/settings.php`:

```php
'repositories' => [
    'team_database' => [
        'type' => Wsmallnews\Support\Tenant\Settings\Repositories\DatabaseSettingsRepository::class,
        'model' => null,
        'table' => 'sn_team_settings',
        'connection' => null,
    ],
],
```

For activity log, point `activity_model` at `Wsmallnews\Support\Models\Activity`. For media library, point `media_observer` at `Wsmallnews\Support\Observers\MediaLibrary\MediaObserver`; the default observer deletes files along with the media record, which breaks order items that still reference them.

## Install commands

`PackageInstallCommand` is the base command for extension packages:

```php
use Wsmallnews\Support\Commands\PackageInstallCommand;

class CmsInstallCommand extends PackageInstallCommand
{
    protected string $packageName = 'sn-cms';

    protected function afterPublish(): void
    {
        // package-specific publish steps
    }
}
```

What it does:

1. `InstallPlanner` builds the dependency plan by reading the `wsmallnews/*` requires of the target package and its dependencies from `vendor`, then ordering them topologically. Only packages exposing an `{sn-xxx}:install` command are planned.
2. Dependencies are installed silently (`--as-dependency`): publish only, no prompts.
3. The package publishes its own config and migrations, then runs `afterPublish()`.
4. A single `migrate` prompt runs every pending migration.
5. Only the package being installed is asked to star its repository.

Options: `--no-deps` skips dependency installation; `--as-dependency` is internal and performs no interaction.

## Frontend styles

Include `resources/css/index.css` from your app stylesheet:

- `tokens.css` — design tokens; the only entry point for theming. Geometric tokens flip at `lg`, colour tokens in `.dark`.
- `utilities.css` — shared `sn-*` utilities (containers, badges, buttons, text, empty states, skeletons).
- `content.css` — `sn-prose` typography for rendered content.
- `filament.css` — `sn-filament-*` overrides that depend on Filament internals, available only to packages that inherit support.

Conventions:

- Compose a base class (shape and size) with a modifier class (colour or state), e.g. `sn-badge sn-badge-sm sn-badge-danger`.
- Use `sn-gap`, `sn-margin` and `sn-padded` for block-level spacing instead of fixed `gap-4` / `p-4`.
- `sn-page` is the page skeleton, `sn-content` the content column, `sn-page-x` the horizontal safe padding inside full-width bands.
- `sn-container` is a content card (background, border, shadow), not a generic layout wrapper.
- `sn-hover` / `sn-link` / `sn-active` only style descendants when the outer element is a `sn-container`.
- For dynamic colours use `sn_badge_color()` / `sn_text_color()`, which return `['class' => ..., 'style' => ...]`.

## Utility API

| Method | Purpose |
|---|---|
| `Utils::getConfig($key, $default)` | Read `sn-support` config with dot notation |
| `Utils::getModel($name, $throw = true)` | Resolve a configured model class; `false` allows `null` |
| `Utils::getContentModel()` / `getCompositionModel()` / `getPageModel()` / `getScheduledTaskModel()` / `getSmsLogModel()` | Configured model classes |
| `Utils::getFilesystemDisk()` | Upload disk, falling back to Filament's default |
| `Utils::getScopeFromInstances($configKey, $key = null)` | Resolve a `ScopeableContext` from a module's scopeables |
| `Utils::isTenancyEnabled()` / `getTenantModel()` | Tenancy state and model |
| `generate_slug()` | Slug with word-boundary truncation and a random fallback |
| `text_highlight($text, $query)` | Escaped keyword highlighting with `<mark>` |
| `sn_badge_color()` / `sn_text_color()` | Semantic colour to class and style |
| `href_format($url, $newTab, $spaMode)` | `href` / `target` / `wire:navigate` attributes |
| `files_url($files, $disk)` | File URL resolution |
| `exception_log($e, $name, $message)` | Structured exception logging |
| `through_cache($key, $callback, $store, $force, $ttl)` | Cache-through reads |
| `tree_to_flatten($tree)` | Flatten a tree collection |
| `remove_query_param_from_url($url, $keys)` | Strip query parameters |
| `scopeable_context()` / `scopeable_query()` | Build and apply scopes |

## Testing

```bash
composer test
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

See [CONTRIBUTING](.github/CONTRIBUTING.md).

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

The MIT License (MIT). See [License File](LICENSE.md).
