# Feed（RSS 流）

> 按站点注册命名的 RSS 2.0 流，由模块在自己的站点路由组内暴露两个端点：模块聚合流与具名流。

## 1. 它是什么，为什么需要

**是什么**：RSS 2.0 是一种订阅格式——网站把最新内容输出成 XML，读者用 RSS 阅读器（Feedly、Inoreader、FreshRSS 等）订阅后，不用反复访问网站就能收到更新。

**为什么提供**：

- 内容分发不依赖平台算法或登录态，读者主动订阅；
- 第三方聚合、机器人、内容同步可以直接消费标准 XML；
- 多站点 / 多租户下每个站点可以暴露不同的流，互不混淆；
- 一个模块的多条流可以用模块聚合端点一次订阅。

本包解决的是“注册与隔离”：模块声明要暴露哪些流、每个流取什么数据，渲染时自动跟随当前租户与 scope。

## 2. 快速使用

```php
use Wsmallnews\Support\Facades\Feed;

// 站点级选项与频道信息：站点 feed.* 整节透传（uri/limit/cache_ttl 等行为参数），
// title/description/link 是模块聚合端点（/{prefix}/feed）的 <channel> 元数据
Feed::config('sn-cms', [
    'title' => fn () => '站点名',
    'description' => fn () => '站点描述',
    'link' => fn () => $site->route('index'),
], $site);

// 具名流：名称是 URL 路径段；同名重复注册会整条覆盖
Feed::register('sn-cms', 'posts', [
    'title' => fn () => '站点名 - 文章',
    'limit' => 50,
    'items' => fn (): iterable => Post::query()->published()->latest()->get()->map(fn ($post) => [
        'title' => $post->title,
        'url' => $site->route('posts.show', $post),
        'updated_at' => $post->published_at,
    ]),
], $site);

// 端点（在站点路由组内调用，必须早于 pages/{slug} 这类动态段）
Feed::routes('sn-cms', $site);

// 渲染（模块聚合流；具名流传第三个参数）
Feed::render('sn-cms', $site);
Feed::render('sn-cms', $site, 'posts');
```

## 3. 端点与访问地址

`Feed::routes($module, $site)` 只注册两个端点，路径都在**站点自己的 URL 前缀**下。端点路径经 `resolveConfig` 统一解析：模块声明（站点 `feed.*` 整节透传）> `sn-support.feeds.uri`（默认 `feed`）。每条路由带两个默认值 `feed_module`（所属模块）与 `feed_site`（站点 id），控制器会把它们与 `sn-resolve-site-context` 解析出的上下文比对，不一致直接 404：

| 端点 | 内容 |
|---|---|
| `{prefix}/feed` | 该模块在该站点的所有可见流合并（按 `updated_at` 倒序，`feed.aggregate_limit` 覆盖链截断） |
| `{prefix}/feed/{name}` | 单个具名流，用自己的 `limit` 与顺序 |

> `Feed::render()` 的 `$name = null` 是**模块聚合流**（该模块所有具名流合并），没有独立路由——旧版全局 `/feed` 已退役。

同一个流在不同站点的地址，由站点的 `prefix` / `domain` 决定：

**单站点（本仓库现状）**：site `sn-cms:main`，`prefix = cms`

| 用途 | 地址 | 路由名 |
|---|---|---|
| 模块聚合 | `/cms/feed` | `sn-cms.main.feed` |
| 具名流 | `/cms/feed/posts` | `sn-cms.main.feed.show` |

**多租户（验收站现状）**：`prefix = cms/{tenant:slug}`，站点仍只有一个

| 租户 | 模块聚合 | 具名流 |
|---|---|---|
| first | `/cms/first/feed` | `/cms/first/feed/posts` |
| second | `/cms/second/feed` | `/cms/second/feed/posts` |

路由名不变，租户参数由 `sn_route()` 按当前 `current_tenant()` 补齐。

**多站点（同一面板）**

| site | prefix | 具名流地址 | 路由名 |
|---|---|---|---|
| `main` | `cms` | `/cms/feed/posts` | `sn-cms.main.feed.show` |
| `campaign` | `cms/campaign` | `/cms/campaign/feed/posts` | `sn-cms.campaign.feed.show` |

**独立域名**

| site | domain | prefix | 具名流地址 |
|---|---|---|---|
| `first` | `first.test` | `/` | `https://first.test/feed/posts` |
| `campaign` | `campaign.test` | `/` | `https://campaign.test/feed/posts` |

模板里不要手写地址，用 `Feed::feedUrl('posts', $site)`，或 `$site->route('feed.show', ['name' => 'posts'])`。

## 4. 运行逻辑

1. `RegisterSiteFeed`（SiteService）对每个站点执行，模块 id 取自插件；
2. `config()` 写该模块在该站点的选项（站点 `feed.*` 整节透传 + 频道元数据闭包兜底），`register(s)()` 写流定义；
3. `Feed::routes()` 在站点路由组内注册两个端点，并把完整路由名登记到 `$moduleRouteNames`（SiteService 管道保证 config 先于路由注册，透传的 `feed.uri` 在路由期已可解析）；
4. 请求命中 `FeedController` → 校验路由 `feed_site` 与 `SiteContext` 一致 → 执行 `items` 闭包；
5. 渲染结果整份缓存，TTL 经 `resolveConfig` 解析（`0`/`null` 关闭缓存），`Feed::flush()` 清空。

## 5. 数据结构

```php
// $configs: module => siteId => options
// 站点 feed.* 整节透传（uri/limit/cache_ttl 等行为参数）+ 频道元数据（title/description/link）
[
    'sn-cms' => [
        'sn-cms:main' => [
            'uri' => 'feed',
            'limit' => 50,
            'title' => Closure,
            'description' => Closure,
            'link' => Closure,
        ],
        'sn-cms:campaign' => [
            'limit' => 20,                 // 站点声明覆盖 sn-support.feeds.limit
            'title' => Closure,
            'description' => Closure,
            'link' => Closure,
        ],
    ],
]

// $feeds: module => siteId => name => definition
[
    'sn-cms' => [
        'sn-cms:main' => [
            'posts' => [
                'module' => 'sn-cms',
                'site' => 'sn-cms:main',
                'title' => Closure|string,
                'label' => Closure|string,
                'description' => Closure|string|null,
                'link' => Closure|string,
                'limit' => Closure|int,
                'items' => Closure,        // 必须
            ],
        ],
        'sn-cms:campaign' => [
            'posts' => [/* 同名流，数据不同 */],
        ],
    ],
]

// $moduleRouteNames: module => siteId => 完整路由名
[
    'sn-cms' => [
        'sn-cms:main' => 'sn-cms.main.feed.show',
        'sn-cms:campaign' => 'sn-cms.campaign.feed.show',
    ],
]
```

## 6. 读取 API

| 方法 | 范围 | 说明 |
|---|---|---|
| `getConfig($module, $site, $key = null, $default = null)` | 该模块在该站点的选项 | `$key = null` 返回整个选项数组 |
| `resolveConfig($module, $key, $site, $default = null)` | 统一解析 | 模块声明 > `sn-support.feeds.*` > 默认 |
| `moduleFeeds($module, $site)` | 该模块在该站点的流 | footer 链接与 autodiscovery 用 |
| `getFeed($name, $module, $site)` | 单条 | 不存在返回 `null` |
| `feedUrl($name, $site)` | 单条订阅地址 | 按站点查 `moduleRouteNames` |
| `autodiscoveryTags($module, $site)` | 一组 `<link rel="alternate">` | 布局 head 用（`@snFeeds` 指令） |
| `render($module, $site, $name = null)` | 直接拿 XML | `$name = null` 是模块聚合流，缓存整份 |

## 7. 站点与上下文规则

- 写入口必须显式传 `Site`；读取缺省 `SiteContext`，无站点返回空；
- **只有站点所属模块能注册流**：`$module` 必须等于 `$site->pluginId`，否则注册期抛异常（feed 占用站点 URL，多模块会互相覆盖路由）；
- 同一模块同名流重复注册是**整条覆盖**（不是字段合并）；
- 站点不注册某个模块的流，就是本站没有该模块的 feed，不会继承别的站点；
- `Feed::routes()` 必须在 catch-all 段（如 `navigation/{slug}`）之前注册；
- 行为参数经 `resolveConfig` 统一解析：**模块声明（站点 `feed.*` 整节透传）> `sn-support.feeds.*` 兜底**：
  - 端点路径 `feed.uri` / `feeds.uri`（默认 `feed`）；
  - 单条流条数 `feed.limit` / `feeds.limit`（流定义里显式写的 `limit` 优先级最高）；
  - 聚合流总量 `feed.aggregate_limit` / `feeds.aggregate_limit`；
  - 渲染缓存 `feed.cache_ttl` / `feeds.cache_ttl`（`0`/`null` 关闭缓存）。

## 8. 边界与常见坑

- `items` 必须是闭包，每个条目必须有 `url`，否则被丢弃；
- 模块聚合流按 `updated_at` 倒序并由 `feed.aggregate_limit` 覆盖链截断，具名流保留自己的顺序和 `limit`；
- 缓存按 `tenant + site + host + module + name` 隔离，改内容后记得 `Feed::flush()`；
- 多租户下 `feedUrl()` 依赖当前租户，console 里生成地址要先 `SiteContext::run($site, ..., $tenant)`；
- 路由的 `feed_site` 与当前上下文不一致时返回 404（例如把同一个控制器复用到别的站点路由上）。

## 9. 相关类

| 类 | 职责 |
|---|---|
| `FeedRegistry` | 存储与读取（`$configs` / `$feeds` / `$moduleRouteNames`） |
| `RegisterSiteFeed` | cms 的站点级注册 |
| `FeedController` | 两个端点的渲染 |
| `Feed` Facade | 静态入口 |
