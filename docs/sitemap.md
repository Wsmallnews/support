# Sitemap

> 每个站点一份 `sitemap.xml`（按 URL 来源聚合），`robots.txt` 全局一份。

## 1. 它是什么，为什么需要

**是什么**：`sitemap.xml` 是给搜索引擎的 URL 清单，告诉它站点有哪些页面、最后更新时间；`robots.txt` 是给爬虫的访问规则，并可以指向 sitemap。

**为什么提供**：

- 加快新内容收录，减少漏抓；
- 多站点 / 多租户下每个站点一份 sitemap，互不混合；
- 模块把 URL 来源注册进来即可，不用手写整份 XML；
- robots 统一输出面板路径、模块禁爬规则和每个站点的 Sitemap 行。

本包解决的是“按站点聚合来源 + 全局 robots”，模块只关心自己的 URL 闭包。

## 2. 快速使用

```php
use Wsmallnews\Support\Facades\Sitemap;

// 站点级选项：站点 sitemap.* 整节透传（enabled 是启用门控，不透传）
Sitemap::config('sn-cms', $sitemapOptions, $site)
    ->registers('sn-cms', [
        [
            'key' => 'posts',
            'urls' => fn (): array => Post::query()->published()->get()->map(fn ($post) => [
                'loc' => $site->route('posts.show', $post),
                'lastmod' => $post->updated_at,
            ])->all(),
        ],
    ], $site)
    ->robots('sn-cms', ['disallow' => ['cms/search']]);

Sitemap::render('sn-cms', $site);     // 该模块在该站点的 sitemap XML
Sitemap::renderRobots();    // 全局 robots.txt
```

站点 `sitemap.enabled = false` 时（对齐 feed/search 的门控语义）：`{prefix}/sitemap.xml` 端点不注册、URL 来源不注册（未注册即空），该站点不出现在 robots 的 Sitemap 行——但搜索页的 robots 禁爬规则照常收集（与 sitemap 开关无关）。

## 3. 端点与访问地址

| 端点 | 归属 | 地址 |
|---|---|---|
| sitemap | 每个站点一份 | `{prefix}/sitemap.xml`（`CmsFrontendRoutes` 注册） |
| robots | 全局一份 | `/robots.txt`（support 注册，与站点无关） |

多站点 / 多租户对照：

| 场景 | site | prefix / domain | sitemap 地址 |
|---|---|---|---|
| 单站点 | `sn-cms:main` | `cms` | `/cms/sitemap.xml` |
| 多站点 | `main` | `cms` | `/cms/sitemap.xml` |
| | `campaign` | `cms/campaign` | `/cms/campaign/sitemap.xml` |
| 多租户 | `sn-cms:main` | `cms/{tenant:slug}` | `/cms/first/sitemap.xml`、`/cms/second/sitemap.xml` |
| 独立域名 | `first` | `first.test` + `/` | `https://first.test/sitemap.xml` |

`/robots.txt` 只有一个，内容是“面板路径 + 模块 disallow + 每个前台站点的 Sitemap 行”。带 `{tenant}` 的站点地址不确定具体租户，所以不会出现在根 robots 里。

## 4. 运行逻辑

1. `RegisterSiteSitemap`（SiteService）为每个站点执行：站点 `sitemap.*` 整节透传进模块声明（`enabled` 是启用门控不透传）；`sitemap.enabled = false` 时跳过来源注册；
2. `finish()`（所有站点注册完后执行一次）把各站点的 disallow 规则写入 `robots()`（搜索页禁爬，与 sitemap 开关无关）；
3. 请求 `{prefix}/sitemap.xml` → `Sitemap::render($module, $site)`（模块经路由默认值 `sitemap_module` 绑定）→ 逐个执行该模块的 `urls` 闭包；
4. XML 整份缓存，TTL 解析链 = **站点 options > `sn-support.sitemap.cache_ttl`**（`null`/`0` 关闭缓存），`flush()` 清除；
5. `/robots.txt` 由 support 的全局路由输出，与站点无关。

## 5. 数据结构

```php
// $robotsRules: module => rules（模块级，全局文件）
[
    'sn-cms' => ['disallow' => ['cms/search', 'cms/search/*']],
]

// $configs: module => siteId => options
// 站点 sitemap.* 整节透传（enabled 除外），如 cache_ttl
[
    'sn-cms' => [
        'sn-cms:main' => [
            'cache_ttl' => 3600,
        ],
        'sn-cms:campaign' => [
            'cache_ttl' => 0,        // 站点声明覆盖 sn-support.sitemap.cache_ttl
        ],
    ],
]

// $sources: module => siteId => list<source>
[
    'sn-cms' => [
        'sn-cms:main' => [
            [
                'key' => 'posts',       // 必填
                'urls' => Closure,      // 必填：返回 [['loc' => '...', 'lastmod' => '...'], ...]
            ],
        ],
        'sn-cms:campaign' => [
            [
                'key' => 'posts',
                'urls' => Closure,      // 同一模块在不同站点可以是不同的 URL 集合
            ],
        ],
    ],
]
```

## 6. 站点与上下文规则

- 来源按站点分区，写入必须显式传 `Site`；
- 读入口必填 `Site`：`render($module, $site)` / `getSources($module, $site)` / `resolveConfig($module, ...)`；sitemap 路由带 `sitemap_module` + `sitemap_site` 默认值，控制器与 `SiteContext` 比对，不一致 404；
- `robots.txt` 遍历 `SiteRegistry`，只对 `routable` 且路由无参数的站点输出 `Sitemap:` 行；
- 选项解析链：**站点 options（`sitemap.*` 整节透传进模块声明）> `sn-support.sitemap.*`**（`cache_ttl`）；sitemap 聚合多模块的来源，配置本身是站点级的。

## 7. 边界与常见坑

- `urls` 必须返回数组，`loc` 为空会被丢弃；`lastmod` 无法解析时该字段省略；
- 带 `{tenant}` 参数的站点不会出现在根 `robots.txt` 的 Sitemap 行（根文件不知道具体租户）；
- 缓存 key 含 `tenant + site + host + 模块集合`，内容变更后要 `Sitemap::flush()`；
- `Sitemap::flush()` 不传站点会清所有已注册站点 + 无站点缓存；
- `sitemap.enabled = false` 只影响该站点的 sitemap 端点与来源（未注册即空），robots 的搜索禁爬照常。

## 8. 相关类

| 类 | 职责 |
|---|---|
| `SitemapRegistry` | 存储与渲染（`$robotsRules` / `$configs` / `$sources`） |
| `RegisterSiteSitemap` | cms 的站点级来源与 robots 规则 |
| `SitemapController` | 站点 sitemap 端点 |
| `Sitemap` Facade | 静态入口 |
