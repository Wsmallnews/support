# Seo（页面 SEO）

> 按站点注册模块级 SEO 默认值，页面渲染时与页面自己的声明合并输出：`<head>` 标签、OpenGraph、Twitter Card 与 JSON-LD 结构化数据。

## 1. 它是什么，为什么需要

**是什么**：SEO 元信息是给搜索引擎和社交平台看的页面描述，包括 title、description、canonical、robots、favicon、OpenGraph、Twitter Card、JSON-LD、统计代码。

**为什么提供**：

- 影响搜索结果的标题 / 摘要和社交分享卡片；
- 站点级默认值（站名、默认图、favicon、统计代码）不用每个页面重复；
- 多站点 / 多品牌可以各自配置，Settings 值用闭包延迟到渲染期，自动跟随租户；
- 页面只声明差异（标题、封面、结构化数据），合并与回退由本包统一处理。

本包解决的是“默认值 + 页面覆盖”的合并与站点隔离：模块注册默认值，页面只声明差异。

## 2. 快速使用

```php
use Wsmallnews\Support\Facades\Seo;

// 站点级默认值（SiteService 中注册；闭包渲染期才解析，自动跟随租户）
Seo::config('sn-cms', function (): array {
    $general = app(GeneralSettings::class);

    return [
        'site_name' => ...,           // title 后缀 / og:site_name / Article publisher
        'description' => ...,         // meta description 与 og:description 的兜底
        'image' => ...,               // og:image 兜底（默认分享图）
        'favicon' => ...,             // <link rel="icon">
        'analytics_code' => ...,      // 统计代码原文，@snSeoAnalytics 原样输出
    ];
}, $site);
```

```php
// 页面级声明（Livewire 组件 mount/render 中，链式调用，经 __call 转发到 SeoPage）
// 首页：WebSite 结构化数据
Seo::website();

// 列表页
Seo::title(__('文章列表'));

// 详情页：article() 同时组装 Article JSON-LD 并把 og:type 切成 article
Seo::title($post->title)
    ->description($post->description)
    ->image($post->coverUrl)
    ->article([
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'author' => $post->publisher?->name,
    ]);

// 任意页：自定义 JSON-LD 节点、禁止索引
Seo::title(__('登录'))
    ->robots('noindex')
    ->jsonLd(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', /* ... */]);
```

布局中输出：`@snSeo`（`<head>` 内）、`@snSeoAnalytics`（`</body>` 前）。

## 3. 输出内容（render 合并规则）

`@snSeo` 输出的标签与取值来源（**页面声明优先，站点默认兜底**）：

| 标签 | 取值 |
|---|---|
| `<title>` | 页面标题 + `" - " + site_name`；页面未声明时仅 `site_name` |
| `<meta name="description">` | 页面 description > 站点默认；都没有则不输出 |
| `<meta name="robots">` | 仅页面声明时输出（`noindex` 等），默认值不参与 |
| `<link rel="canonical">` | 页面 `url()` > `request()->url()`；永远转绝对地址 |
| `<link rel="icon">` | 仅站点默认 `favicon` |
| OpenGraph | `og:site_name` / `og:title`（同 `<title>` 规则）/ `og:type`（默认 `website`，`article()` 自动切换）/ `og:url`（绝对地址）/ `og:locale`（跟随应用语言）/ `og:description` / `og:image`（有才输出） |
| `<meta name="twitter:card">` | 有分享图 `summary_large_image`，无图 `summary`（自动选择） |
| JSON-LD | 三类：`website()` 的 WebSite 节点（首页用）、`article()` 的 Article 节点、`jsonLd()` 的自定义节点 |

Article 节点字段：`headline` = 页面标题、`description` / `image` / `url` 复用上面已解析的值、`datePublished` / `dateModified` / `author` 来自 `article($extra)`；`author` 未传时回落 `Organization`（site_name），`publisher` 恒为 `Organization`（site_name）。值为空的字段整体剔除。

所有 URL 类值（canonical / og:url / og:image / favicon / Article image）自动转绝对地址——已是 `http(s)://` 或 `data:` 的原样保留，相对路径经 `url()` 补全。JSON-LD 输出时转义 `</`，防止值里的 `</script>` 提前闭合标签。

## 4. 运行逻辑

1. `RegisterSiteSeo`（SiteService）对每个站点注册默认值闭包；
2. 页面路由的 `sn-resolve-seo:{module}` 中间件调用 `Seo::init($module)`：重置页面上下文并绑定模块归属（`getFor()`）；
3. 页面组件链式调用 `title()/description()/...` 写入 `SeoPage`；
4. 布局 `@snSeo` 渲染时 `defaults()` 解析模块 + 当前站点的默认值，`SeoPage::render($defaults)` 用页面值覆盖；
5. 默认值按 `siteId + module` 标记缓存，`init()` / `config()` / 站点切换都会失效重算。

`Seo` 是容器单例，`SeoPage` 每请求由 `init()` 重建——Livewire update 请求不跑 `sn-resolve-seo`，只重渲染组件片段、不输出布局 `<head>`，因此无需重建。

## 5. 数据结构

```php
// $configs: module => siteId => array|Closure（闭包渲染期才解析，跟随租户）
[
    'sn-cms' => [
        'sn-cms:main' => Closure,
        'sn-cms:campaign' => Closure,   // 同一模块，另一个站点的默认值
    ],
]

// 解析后的默认值（defaults()）
[
    'site_name' => 'Example',          // 兜底 config('app.name')
    'description' => null,
    'image' => null,
    'favicon' => null,
    'analytics_code' => null,
]

// 页面上下文（SeoPage，每请求重建）
SeoPage {
    title: ?string,
    description: ?string,
    image: ?string,
    type: string,                       // 默认 'website'，article() 切为 'article'
    url: ?string,                       // 缺省用 request()->url()
    robots: ?string,
    jsonLds: array,                     // jsonLd() 追加的自定义节点
    articleExtra: ?array,               // article() 的 datePublished/dateModified/author
    website: bool,                      // website() 标记，输出 WebSite 节点
}
```

## 6. 站点与上下文规则

- 默认值按 `module => siteId` 分区，同一模块在不同站点互不覆盖；
- 注册必须显式传 `Site`；读取用当前 `SiteContext`（本服务是注册表里保留 `?Site` 缺省语义的例外之一，供无站点上下文的消费方安全调用）；
- 没有站点时 `defaults()` 只剩 `site_name = config('app.name')` 兜底，不借用其他站点；
- `forModule` 由中间件绑定，页面组件无需再传模块。

## 7. 边界与常见坑

- 闭包只在渲染 / `defaults()` 时执行，适合读 Settings（跟随租户）；
- `init()` 由 `sn-resolve-seo` 中间件保证每页面首渲染调用一次，Livewire update 不会重复；
- 页面未声明 `title` 时回落到站点 `site_name`，没有再往下的兜底；
- SEO 没有自己的端点：它输出到页面 `<head>`，地址就是当前页面的地址；
- 嵌入式编排（embedded）场景下组件应跳过 SEO 声明，让渡给宿主页面，避免块内容覆盖页面标题（cms 文章组件的做法）；
- `robots` 只有页面声明才输出——想整站 noindex 应在页面/布局层声明，而不是写在站点默认值里。

## 8. 相关类

| 类 | 职责 |
|---|---|
| `Seo` | 站点默认值注册与解析、页面上下文入口（`__call` 转发页面声明） |
| `SeoPage` | 页面级标签与 JSON-LD 组装渲染 |
| `RegisterSiteSeo` | cms 的站点级默认值注册 |
| `ResolveSeo` 中间件 | 首渲染重建页面上下文并绑定模块 |
| `Seo` Facade | 静态入口 |
