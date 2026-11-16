# Support 功能文档

support 包按功能拆分的实现说明。每篇文档包含：定位 → 快速使用 → 端点与地址 → 运行逻辑 → 数据结构 → 站点规则 → 边界。

## 阅读顺序

| 顺序 | 文档 | 说明 |
|---|---|---|
| 1 | [site.md](site.md) | Site / SiteConfig / SiteContext / SiteRegistry / SiteService：所有功能的基础 |
| 2 | [registries.md](registries.md) | 注册表共同约定：按站点分区、读写规则、无站点行为 |
| 3 | [feed.md](feed.md) | RSS 流：注册、端点、渲染、缓存 |
| 4 | [search.md](search.md) | 全局搜索：来源、模块选项、引擎 |
| 5 | [seo.md](seo.md) | SEO：站点默认值 + 页面上下文 |
| 6 | [sitemap.md](sitemap.md) | sitemap.xml 与 robots.txt |
| 7 | [composition.md](composition.md) | 内容编排：类型/槽位注册、行槽解析、上下文传递、页面与侧栏渲染 |

## 一句话模型

模块在**站点**上注册功能（`SiteService::register(Site)`），功能数据按 `module => siteId` 存进各自的 registry；请求期通过 `SiteContext` 找到当前站点，再读该模块在该站点的分区。

## 约定

- 文档中文，代码标识符英文；
- 示例中的 `$site` 都是 `Wsmallnews\Support\Features\Site\Site` 实例；
- 写入类 API 一律要求显式 `Site`；Feed / Search / Sitemap 的读入口**必填 `Site`**，没有缺省上下文；例外是 `UserConfig` / `Seo` 两个上下文状态服务，读取缺省取 `SiteContext`，无站点返回空且不跨站点借用数据。
