# Search（全局搜索）

> 按站点注册搜索来源与选项，统一由搜索组件查询，结果按 group 分组。

## 1. 它是什么，为什么需要

**是什么**：站内搜索——用户输入关键词，系统在指定模块注册的来源（可多张表）里查找，按分组返回结果，并跳转到对应详情页。

**为什么提供**：

- 内容多起来后，导航和列表页不够用，搜索是主要的找回入口；
- 各模块模型不同，统一注册后由一个组件查询，避免每个模块各写一套；
- 模块可以自己决定展示方式（下拉框 / 独立页）、字段、排序和链接；
- 结果按 group 分组，同一模块多个来源的结果可以一起展示。

本包解决的是“来源注册 + 按站点取配置”：模块只声明可搜模型和选项，查询与渲染交给搜索组件。

## 2. 快速使用

```php
use Wsmallnews\Support\Facades\Search;
use Wsmallnews\Support\Features\Search\SearchSource;

Search::config('sn-cms', [
    'display' => 'page',
    'page' => fn (?string $query) => $site->route('search', ['q' => $query]),
], $site)->registers('sn-cms', [
    new SearchSource([
        'key' => 'post',
        'model' => Post::class,
        'group' => '文章',
        'query' => fn ($query) => $query->published(),
        'scopeable' => $site->scopeable(),
        'url' => fn ($record) => $site->route('posts.show', $record),
    ]),
], $site);
```

## 3. 条目渲染与自定义结果（view / render / results）

三个"接管渲染或数据"的来源选项，接管程度递进：

| 选项 | 接管什么 | 优先级 |
|---|---|---|
| `view` | 条目内容区的视图（换模板，数据仍来自引擎） | 兜底 |
| `render` | 条目内容区的 HTML（闭包直接产出） | **高于 view** |
| `results` | 整个结果集（绕过引擎，自己造结果） | 最高，与 view/render 独立 |

默认链路是：引擎查出模型 → `toResult()` 映射成 `SearchResult` → 默认统一视图渲染（封面占位 + `text_highlight` 高亮标题/描述 + badge）。**外层链接由 support 统一包裹**（`$result->url` 有则 `<a>`、无则 `<div>`），view / render 只负责条目内容区。

### view：自定义条目视图

```php
new SearchSource([
    'key' => 'post',
    'model' => Post::class,
    'group' => '文章',
    'view' => 'sn-cms::components.search.post-item',   // cms 的文章条目：标题高亮 + 发布日期
]);
```

视图契约：接收 `$result`（`SearchResult` 对象，字段见第 6 节；`->record` 是原始模型）和 `$query`（当前关键词，做高亮用）。未声明 `view` 的来源兜底默认统一模板（`SearchSource::DEFAULT_ITEM_VIEW`：封面 + 高亮标题/描述 + badge）。

```blade
{{-- 自定义条目视图示例：只渲染内容区，外层 <a>/<div> 由 support 包裹 --}}
<div class="flex flex-col gap-0.5">
    <span class="font-medium">{!! text_highlight($result->title, $query) !!}</span>
    <span class="text-xs text-gray-500">{{ $result->record?->published_at?->format('Y-m-d') }}</span>
</div>
```

### render：条目渲染闭包（优先于 view）

```php
new SearchSource([
    'key' => 'post',
    'model' => Post::class,
    'group' => '图文',
    'render' => fn ($result, $query) => '<em class="font-bold">'
        . text_highlight($result->title, $query)
        . '</em>',
]);
```

签名 `fn (SearchResult $result, string $query): string`，返回的 HTML 原样输出（`{!! !!}` 通道），适合一两行内联结构、不值得建视图文件的场景；同一来源同时声明 `render` 和 `view` 时 `render` 胜出。

### results：完全自定义结果（绕过引擎）

```php
new SearchSource([
    'key' => 'quick-links',
    'group' => '快捷入口',
    // 收到当前关键词 $query；返回任意可 collect 的 SearchResult 列表
    'results' => fn (string $query) => collect([
        new SearchResult(key: 'quick-links', group: '快捷入口', title: '后台面板', url: '/admin'),
        new SearchResult(key: 'quick-links', group: '快捷入口', title: '帮助中心', url: '/help'),
    ]),
]);
```

`results` 是一个闭包（接收当前关键词），返回 `SearchResult` 集合或等价数组，**整个来源不走引擎**：不查模型、`model` 可省略，`query` / `scout` / `scopeable` / `fields` 均不生效，且**返回多少就展示多少**——`limit` 截断与模块 `results_limit` 都不作用于它，条数在闭包里自己控制。结果里的 `record` 可省略（没有原始模型），适合快捷入口、聚合页、外部数据等"没有对应模型行"的搜索位。

## 4. 端点与访问地址

搜索本身没有独立控制器，入口是站点路由组里的搜索结果页，路径取自站点选项 `route_uris.search`（默认 `search`）：

| 模式 | 地址 | 说明 |
|---|---|---|
| `display = page` | `{prefix}/search?q=关键词` | 独立结果页，`CmsFrontendRoutes` 注册 `...->name('search')` |
| `display = dropdown` | 无地址 | 输入框内联展示前几条结果 |

只有 `search.enabled = true` 且 `display = page` 时，cms 才注册结果页并写入 robots 的 disallow。

多站点 / 多租户对照：

| 场景 | site | prefix | 结果页 |
|---|---|---|---|
| 单站点 | `sn-cms:main` | `cms` | `/cms/search?q=laravel` |
| 多站点 | `main` | `cms` | `/cms/search?q=laravel` |
| | `campaign` | `cms/campaign` | `/cms/campaign/search?q=laravel` |
| 多租户 | `sn-cms:main` | `cms/{tenant:slug}` | `/cms/first/search?q=laravel`、`/cms/second/search?q=laravel` |

站点用 `Search::resolvePage($module, $site, $query)` 生成跳转地址；它读站点选项里的 `page`（闭包）或字符串配置，不要在模板里手拼路径。

## 5. 运行逻辑

1. `RegisterSiteSearch` 对每个站点注册选项与来源：站点 `search.*` 整节透传进模块声明（`enabled` 是启用门控不透传，`page` 未声明时用包内结果页路由闭包兜底）；
2. 搜索组件挂载时调 `Search::resolveConfig($module, 'display', $site)`，决定下拉或跳独立页；
3. 查询时 `search()` 按模块取来源，解析引擎（database / scout / 自定义）；
4. 每个来源执行 `query` / `scout`，结果按 `group` 合并；
5. 结果链接由来源自己的 `url` 闭包生成。

## 6. 数据结构

```php
// $configs: module => siteId => options
[
    'sn-cms' => [
        'sn-cms:main' => [
            'display' => 'dropdown',      // dropdown | page
            'page' => Closure|string|null,
            'limit' => 8,                 // 每来源默认条数（与来源声明的 limit 同名）
            'engine' => 'database',       // database | scout | Engine 类名
            'split_terms' => true,
            'terms_operator' => 'and',
            'show_search_button' => false,
            'case_insensitive' => true,
        ],
        'sn-cms:campaign' => [
            'display' => 'page',
            'page' => Closure,
        ],
    ],
]

// $sources: module => siteId => Collection<SearchSource>
[
    'sn-cms' => [
        'sn-cms:main' => collect([
            SearchSource { options: [
                'key' => 'post',
                'model' => Post::class,
                'group' => '文章',
                'fields' => ['title', 'description'],
                'limit' => 8,
                'sort' => 0,
                'query' => Closure,
                'scopeable' => ['scope_type' => 'sn-cms', 'scope_id' => 0],
                'url' => Closure,
                'view' => 'sn-cms::components.search.post-item',   // 缺省 DEFAULT_ITEM_VIEW
                'render' => Closure,                               // 优先于 view
                'results' => Closure,                              // 声明后整个来源绕过引擎
                'visible' => Closure,
            ] },
        ]),
        'sn-cms:campaign' => collect([
            SearchSource { options: [/* 同一个 key，不同 scope / 数据 */] },
        ]),
    ],
]

// 单条搜索结果（SearchResult，view / render / results 消费的数据契约）
SearchResult {
    key: string,                 // 来源 key
    group: string,               // 分组标签
    title: string,               // 纯文本标题，视图层负责转义/高亮
    description: ?string,
    coverUrl: ?string,
    url: ?string,                // 无值时条目渲染为不可点
    badge: ?string,
    morphType: ?string,          // 模型 morph 别名
    record: ?Model,              // 原始模型；results 自定义结果可省略
}
```

## 7. 站点与上下文规则

- 来源和选项都按站点分区，写入必须显式传 `Site`；
- 读入口 `(module, Site $site)` 必填（`getConfig` / `getSources` / `search` / `itemRenderers` / `resolvePage` / `resolveConfig`），没有缺省上下文；组件内部用 `SiteContext::requireSite()` 取当前站点；
- 选项解析顺序：组件属性 → 站点模块选项 → `sn-support.search` 全局配置；
- 站点没注册来源即没有该模块的搜索，不会借用其他站点。

## 8. 边界与常见坑

- `model` 必填；morph alias 与类名都支持；
- 组件的 `module` 属性必填（`<x-sn-support-search module="sn-cms" />`）：空或未注册的模块在搜索时抛 `SupportException`，没有"不传模块搜全部"的用法；
- `register()` 优先收 `SearchSource` 实例（直接持有，构造时已完成 morph 别名到类名的规范化）；也兼容数组（注册表内经构造函数规范化）；
- 同一模块重复注册同一 `key` 会覆盖（用于替换内置来源）；
- `search($module, ...)` 的模块必须在本站点有来源，否则抛 `SupportException`；
- 条数优先级：显式参数 > 来源 `limit` > 模块声明 `limit` > 全局；声明了 `results` 的来源不受任何条数限制（闭包自己控制）；
- `view` / `render` 只渲染条目内容区，外层链接由 support 统一包裹（`url` 有无决定 `<a>` / `<div>`），自定义内容里不要再包链接。

## 9. 相关类

| 类 | 职责 |
|---|---|
| `SearchRegistry` | 存储与查询（`$configs` / `$sources`） |
| `SearchSource` | 单个来源的选项解析与结果映射 |
| `Engines\*` | database / scout / 自定义引擎 |
| `Livewire\Components\Search` | 搜索组件 |
