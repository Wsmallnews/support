# 注册表共同约定

> 所有按站点注册的功能（Feed / Search / Sitemap / Seo / UserConfig / Sidebar）共用同一套存储与读取规则。

## 1. 它是什么，为什么这样设计

**是什么**：注册表是模块功能的公共存储层——各模块把配置和条目注册进来，渲染 / 查询时按站点读取。

**为什么按站点分区**：同一套代码要在多个站点间隔离数据；如果保留模块级兜底，“站点没配置”和“站点明确配置为空”就无法区分，容易串数据。所以写入必须带 `Site`，读取只认当前站点。

**为什么模块在前**：注册代码永远按模块写——一个 `SiteService` 属于一个模块，内部遍历该模块的所有站点；配置根也是模块（`sn-cms.options.*`）。`module => siteId` 与写入顺序、`ModuleRegistry`、Feed 的路由名登记同轴，`siteId` 只是模块内部的作用域键。

**它是一个二维矩阵**：一个模块可以服务多个站点，一个站点也可以有多个模块的条目（cms + shop 都有 search）。两层都必须存在，这里的取舍只是索引顺序。唯一的归属例外是 Feed——它占用站点 URL，只允许站点所属模块注册（见第 3 节）。

## 2. 存储形态

```php
$configs = [                          // 模块选项
    'sn-cms' => [                     // 模块 id（通常等于 plugin id）
        'sn-cms:main' => ['feed' => ['limit' => 50]],
        'sn-cms:campaign' => ['feed' => ['limit' => 20]],
    ],
    'sn-shop' => [
        'sn-cms:main' => ['...' => '...'],
    ],
];

$items = [                            // 模块条目（feed / source / menu）
    'sn-cms' => [
        'sn-cms:main' => [/* posts feed */],
        'sn-cms:campaign' => [/* campaign 的 posts feed */],
    ],
];
```

没有模块级兜底，也没有 `configGlobal()`。站点没注册某个模块，就是没有。

> `module` 是模块 id，不是 Filament plugin id。两者通常同名（cms 用 `CmsPlugin::getId()`），但 `sn-support` 就没有 plugin；`Module::$plugin` 本身是可空的。

## 3. 写入

签名统一为 `config(string $module, array $config, Site $site)`、`register/registers(..., Site $site)`：

- `Site` 必填，不能依赖当前上下文；
- 同一个模块对同一站点重复 `config()` 是浅合并，后写覆盖；
- `registers()` 就是逐个 `register()` 的语法糖：追加 + 同名覆盖（Feed 的 key 是流名、条目是数组；Search / Sitemap 是列表，Search 的条目是 `SearchSource` 实例）；空数组表示不注册任何条目，等价于本站没有该模块的条目。

**归属规则按“是否占用站点 URL”区分**：

| 注册表 | 跨模块注册 | 原因 |
|---|---|---|
| Feed | ❌ 禁止（`$module` 必须等于 `$site->pluginId`） | 占用 `{prefix}/feed/{name}`，路由名也来自站点 group，多模块会互相覆盖 |
| Search / Sitemap | ✅ 允许 | 一个结果页 / 一个 sitemap 文件本来就聚合多个模块，不存在 URL 冲突 |

## 4. 读取

Feed / Search / Sitemap 的读入口**必填 `Site`**（第二参数），没有缺省上下文；组件与控制器用 `SiteContext::requireSite()` 或路由默认值取得站点。

选项类读取走统一的 `resolveConfig($module, $key, Site $site, $default = null)`：**模块声明（扩展包把站点 `options` 的对应节整节透传进 `config()`）> `sn-support.*` 全局配置 > 默认值**。Feed 与 Search 各有一份，语义一致；扩展包新增行为参数时不需要自己拼覆盖链。

例外是两个"上下文状态服务"：`UserConfig::getConfig()` 与 `Seo::defaults()` 保留缺省 `SiteContext` 的语义——它们会被无站点上下文的消费方调用（模型加密器、面板、页面渲染），无站点时返回 null/默认值，**绝不跨站点借用数据**。

## 5. 注册时机

每个模块把自己的注册逻辑写成 `SiteService`：

```php
final class RegisterSiteFeed extends SiteService
{
    public function register(Site $site): void { /* Feed::config(..., $site) */ }

    public function finish(): void { /* 所有站点注册完后执行一次 */ }
}
```

`SiteServicePipeline` 外层循环站点、内层循环服务，`finish()` 只跑一次。

## 6. 各注册表的属性

| 注册表 | 属性 | 形态 |
|---|---|---|
| `FeedRegistry` | `$configs` | module => siteId => options |
| | `$feeds` | module => siteId => name => definition |
| | `$moduleRouteNames` | module => siteId => routeName |
| `SearchRegistry` | `$configs` | module => siteId => options |
| | `$sources` | module => siteId => Collection<SearchSource> |
| `SitemapRegistry` | `$configs` | module => siteId => options |
| | `$sources` | module => siteId => list<source> |
| | `$robotsRules` | module => rules（模块级，robots.txt 是全局文件） |
| `Seo` | `$configs` | module => siteId => array\|Closure |
| `UserConfig` | `$configs` | module => siteId => array\|Closure |
| `SidebarMenuRegistry` | `$siteMenus` | module => siteId => Collection<menu> |

## 7. 缓存

带缓存的注册表（Feed / Sitemap）cache key 都包含 `tenant + siteId + host`，避免跨租户/站点污染；内容变更后调对应 `flush()`。
