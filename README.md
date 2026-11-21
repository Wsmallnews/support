# Wsmallnews System Base Support Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wsmallnews/support.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/support)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/support/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/wsmallnews/support/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/wsmallnews/support/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/wsmallnews/support/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/wsmallnews/support.svg?style=flat-square)](https://packagist.org/packages/wsmallnews/support)

Base package for the Wsmallnews Filament ecosystem: shared models, component factories, Livewire traits, registries (search, sitemap, feeds, SEO, composition, scheduled tasks, modules), money handling, and the `sn-*` frontend CSS layer.

Every other Wsmallnews package builds on this one. Package-specific conventions for AI agents live in [`resources/boost/guidelines/core.blade.php`](resources/boost/guidelines/core.blade.php).

Repository-only per-feature docs (excluded from release archives): [`docs/README.md`](docs/README.md).

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

- **SupportModel** — base class for every package model, with `SiteScope` and tenant awareness.
- **SiteScope** — `siteContext()`, `panelContext()` and `forScopeable()` query scopes, plus the tenant axis.
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
| `ScopedBySite` | `#[Locked]` `$siteId` / `$tenantId` / `$scopeable`; scope derived from the site |
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
- **Middleware** — `ResolveSiteContext` (site + tenant in one pass) and `ResolveSeo` (aliases `sn-resolve-site-context` / `sn-resolve-seo`).

## Usage

### SupportModel

```php
use Wsmallnews\Support\Models\SupportModel;

class Post extends SupportModel
{
    protected $table = 'posts';
}

Post::query()->forScopeable(['scope_type' => 'blog', 'scope_id' => 1])->get();   // scope + context tenant
Post::query()->siteContext()->get();                                             // current site's scope

$post->getScopeType();               // 'blog'
$post->getScopeId();                 // 1
$post->getScopeable();               // ['scope_type' => 'blog', 'scope_id' => 1]
```

`scope_id` may be `0`, which means "every record of this scope type". Pass it through `forScopeable()` instead of hand-writing `where('scope_id', 0)` so the tenant rule stays in the query.

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

### Scope

Every site-scoped model extends `SupportModel`, which uses the `Models\Concerns\SiteScope` trait:

```php
Post::query()->siteContext();          // current site's scope (+ site mode rule)
Post::query()->siteContext('footer');  // a registered sub-scope of the current site
Post::query()->panelContext($pair);    // panel query, pair mandatory
Post::query()->forScopeable($pair);    // explicit scope + context tenant
Post::query()->forScopeable($pair, withTenant: false);   // record relations: scope only
```

Filament side:

- `Filament\Concerns\RequiresConfiguration` — base contract for a registered component: scope/module accessors throw a readable exception when the component was never registered.
- `Filament\Concerns\CanBeConfigured` — resolves the scope injected at registration and fails closed when none was declared.
- `Filament\Resources\Concerns\ScopedQueries` — `applyScopeToQuery()` scopes a resource query to its declared pair (includes the contract above).
- `Filament\Resources\Concerns\Pages\ForwardsResourceScope` — forwards the resource's scope to its pages.

A misconfigured scope throws `InvalidScopeException` instead of falling back to a `default` instance.

### Configuration mode for resources and pages

```php
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;

final class PostResource extends BaseResource
{
    use CanBeConfigured;
}
```

Registration is owned by the host plugin: site-aware packages use `RegistersSites`
(`site()->resources()/pages()`), which injects `module_id` and derives every scope
from the site. There is no separate config-file registration trait.

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

`SearchRegistry` (facade `Search`) collects searchable sources per site. A site holds exactly one module's search space, so reads take only the site and derive the module from `Site::pluginId`.

```php
use Wsmallnews\Support\Facades\Search;
use Wsmallnews\Support\Features\Search\SearchSource;

Search::config('sn-cms', [
    'display' => 'page',
    'page' => fn (?string $query) => route('cms.search', ['q' => $query]),
], $site)->registers('sn-cms', [
    new SearchSource([
        'key' => 'post',
        'model' => Post::class,
        'group' => 'Posts',
        'query' => fn ($query) => $query->published(),
        'url' => fn ($record) => route('cms.posts.show', $record),
    ]),
], $site);

Search::search('keyword', $site);  // every source of the site; a site without sources throws
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
| `query` | LIKE engine query modifier; receives `($query, Site $site)` |
| `scout` | Scout index modifier; overrides `query` and `fields` |
| `site_scoped` | Isolate rows by the source's site; defaults to `true`. A public model whose table has no scope columns declares `false` — otherwise the first query fails on the missing column |
| `scope_suffix` | Search a declared sub-scope of the site instead of its main scope (e.g. `aaa` for a `PostResource` registered with that suffix). An undeclared suffix throws at registration |
| `title` / `description` / `cover` / `badge` | Display resolvers; default to `HasSnSubject` data |
| `url` | Link resolver; **only** the registrant can provide it, otherwise items are unlinked |
| `view` | Custom item view; falls back to `SearchSource::DEFAULT_ITEM_VIEW` |
| `render` | `fn ($result, $query)` renderer, wins over `view` |
| `visible` | Closure deciding whether the source takes part |
| `results` | Fully custom results, bypassing the engine |

Module options (`Search::config()`) accept **any** key from `sn-support.search` and are stored per site. Resolution order is component property → site declaration → global `sn-support.search` config. There is no module-level fallback: a site that registered no sources simply has none. Re-registering a key overrides the previous source, which lets an app replace a package source.

Writes take `$module` + `$site` (the module is asserted against `$site->pluginId`); reads take only the site — there is no ambient fallback. Components obtain it from the bound `SiteContext` (`SiteContext::requireSite()`).

The registry binds that site to every source on registration, so a source derives its own data space (`SiteRegistry::scopeableFor()`) instead of reading the ambient request context. `query` modifiers receive the same site as their second argument.

Engines: `database` (default, `WHERE ... LIKE`), `scout` (requires `laravel/scout` and `Searchable` on the model), or a class implementing `Engines\Engine`. Engines implementing `ConfigurableEngine` receive the module options.

Search config keys:

| Key | Default | Purpose |
|---|---|---|
| `engine` | `database` | Fallback engine |
| `display` | `dropdown` | `dropdown` or `page` |
| `page` | `null` | Results page: string gets `?q=` appended, closure builds the URL |
| `limit` | `8` | Results per source |
| `split_terms` | `true` | Split the query on whitespace |
| `terms_operator` | `and` | `and` or `or` for the split terms |
| `show_search_button` | `false` | Render a submit button (page mode only) |
| `case_insensitive` | `true` | Case-insensitive LIKE |
| `debounce` | `300ms` | Frontend input debounce |

Components:

```blade
<livewire:sn-support::components.search placeholder="Search…" :limit="5" />
<livewire:sn-support::components.search-results :limit="10" />
```

`components.search` renders a dropdown or a search field; `components.search-results` renders the result area of a dedicated page and keeps `?q=` in sync.

### Sitemap

`SitemapRegistry` (facade `Sitemap`) aggregates URL sources and robots rules. support exposes `/robots.txt` at the root; `sitemap.xml` belongs to a site and is registered inside that site's route group (`CmsFrontendRoutes` does this for cms).

```php
use Wsmallnews\Support\Facades\Sitemap;

Sitemap::config('sn-cms', ['domain' => 'cms.example.com'], $site)
    ->registers('sn-cms', [
        [
            'key' => 'posts',
            'urls' => fn () => Post::query()->published()->get()->map(fn ($post) => [
                'loc' => route('cms.posts.show', $post),
                'lastmod' => $post->updated_at,
            ]),
        ],
    ], $site)
    ->robots('sn-cms', ['disallow' => ['cms/search']]);
```

- `robots.txt` contains the Filament panel paths, the registered disallow rules and one `Sitemap:` line per registered frontend site.
- `Sitemap::render($site)` renders the XML, `Sitemap::getSources($site)` / `getConfig($site)` / `resolveConfig($site, $key)` read it; all reads take only the site.
- The rendered sitemap is cached as a whole; call `Sitemap::flush($site)` after content changes.

Config keys: `enabled` (robots endpoint), `robots_uri`, `cache_ttl` (`null` or `0` disables).

### Feed

`FeedRegistry` (facade `Feed`) publishes RSS 2.0 streams. Unlike the sitemap, a site can expose several named streams, so readers subscribe to what they care about.

```php
use Wsmallnews\Support\Facades\Feed;

Feed::config('sn-cms', [], $site)
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
    ], $site);
```

- `items` is required and must be a closure; every entry needs a `url`.
- Only the module owning the site may register feeds (`$module === $site->pluginId`); a repeated name replaces the previous definition.
- There is no root `/feed`: register the endpoints inside the site route group with `Feed::routes('sn-cms', $site)`, which adds `{prefix}/feed` (site aggregate) and `{prefix}/feed/{name}`. Call it before catch-all segments such as `navigation/{slug}`.
- `Feed::render($site, $name = null)` renders the aggregate stream (or one named feed) programmatically; the aggregate cap follows `resolveConfig`: site declaration (the site's `feed.*` section passed through) > `feeds.aggregate_limit`.
- `Feed::getConfig($site, $key = null, $default = null)` reads site options; `Feed::resolveConfig($site, $key, $default = null)` resolves site declaration > `sn-support.feeds.*` > default.
- `Feed::getFeeds($site)` / `Feed::getFeed($name, $site)` / `Feed::autodiscoveryTags($site)` are the read entries; the last one returns the `<link rel="alternate">` tags for the layout head (`@snFeeds`).
- Reads take only the site (the module is its `pluginId`); there is no ambient fallback.
- Call `Feed::flush()` after content changes.

Config keys (fallbacks; a module passes its site's `feed.*` section through as the declaration): `uri`, `limit`, `aggregate_limit`, `cache_ttl` (`null` or `0` disables). A site exposes exactly one module's streams.

### Seo

`Seo` holds per-module site defaults and the current page context.

```php
use Wsmallnews\Support\Facades\Seo;

// Site defaults, once per site in the site service
Seo::config('sn-cms', [
    'site_name' => fn () => config('app.name'),
    'description' => fn () => null,
    'image' => fn () => null,
    'favicon' => fn () => '/favicon.ico',
    'analytics_code' => fn () => null,
], $site);

// Per page, in the routed page
Seo::init('sn-cms')
    ->title($post->title)
    ->description($post->description)
    ->article(['datePublished' => $post->published_at?->toIso8601String()]);
```

Render in the layout: `@snSeo` in `<head>`, `@snSeoAnalytics` before `</body>`, `@snFeeds` for autodiscovery links (the site is resolved from the request).

- `init()` runs in the `sn-resolve-seo:{module}` route middleware on first render only. Livewire update requests do not re-render the layout, so they need no re-initialisation.
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

Every deployment ships the `teams` table, so `sn-support.tenant_model` is always configured. Whether a request is tenant-scoped is decided by the site mode (frontend) or the panel's tenancy (admin) — never by the config value. `ResolveSiteContext` is registered as a global Livewire persistent middleware (alias `sn-resolve-site-context`): it resolves the site from the route name (or an explicit plugin parameter) and, for Tenant-mode sites, the tenant from the route's `tenant` parameter by `slug` (narrowed by the site's `tenantType`).

The package ships a base tenant model (`Wsmallnews\Support\Models\Team`) for the `teams` table (`name`, `avatar_url`, `slug`, `status`, `options`). Extend it in the host app when the tenant needs app-specific relations, then point the config at the subclass:

```php
// config/sn-support.php
'tenant_model' => App\Models\Team::class,

// app/Models/Team.php
class Team extends \Wsmallnews\Support\Models\Team
{
    public function users(): BelongsToMany { ... }
}
```

`Team` implements Filament's `HasAvatar` / `HasName` and casts `status` to `Enums\TeamStatus` (`enabled` / `disabled`). Availability is data only — enforce it in the tenant middleware that fits your panel/frontend stack.

```php
current_tenant();            // panel: Filament tenant; frontend: SiteContext tenant
is_in_panel();
sn_route('page.show');       // adds the tenant parameter when needed
```

`scopeTenant()` follows the current context: a site filters by its mode
(Tenant / Platform / Aggregate), a panel by its tenancy, and code without
either context reads platform rows. Use `forTenant($id)` / `allTenants()` for
explicit console or cross-tenant reads.

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
| `Utils::getTenantModel()` | Configured tenant model |
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
