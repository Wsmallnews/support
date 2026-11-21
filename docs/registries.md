# 注册表共同约定

> 所有按站点注册的功能（Feed / Search / Sitemap / UserConfig / Seo / Sidebar）共用同一套存储与读取规则。

## 1. 它是什么，为什么这样设计

**是什么**：注册表是模块功能的公共存储层——各模块把配置和条目注册进来，渲染 / 查询时按站点读取。

**为什么按站点分区**：同一套代码要在多个站点间隔离数据；如果保留站点级兜底，"站点没配置"和"站点明确配置为空"就无法区分，容易串数据。所以写入必须带 `Site`，读取只认当前站点。

**为什么存储仍是 `module => siteId`**：注册代码永远按模块写——一个 `SiteService` 属于一个模块，内部遍历该模块的所有站点；配置根也是模块（`sn-cms.options.*`）。模块维度是注册期的天然索引，也是将来放开"一个站点多个模块"时唯一需要改的地方。

**读取只传 `Site`**：当前规则是"一个站点只由一个模块注册"（`$module` 必须等于 `$site->pluginId`），所以读入口不需要模块参数——内部用 `siteModule($site)` 还原出模块再去查 `module => siteId`。写入保留 `$module` 只为在注册期断言归属，避免插件把数据写进一个读取时永远看不到的分区。

## 2. 存储形态

```php
$configs = [                          // 模块选项
    'sn-cms' => [                     // 模块 id（注册站点所属插件）
        'sn-cms:main' => ['feed' => ['limit' => 50]],
        'sn-cms:campaign' => ['feed' => ['limit' => 20]],
    ],
    'sn-shop' => [
        'sn-shop:main' => ['...' => '...'],
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

> `module` 是模块 id，不是 Filament plugin id。两者通常同名（cms 用 `CmsPlugin::getId()`），但 `sn-support` 就没有 plugin；`Module::$plugin` 本身是可空的。归属断言比较的是 `$site->pluginId`。

## 3. 写入

签名统一为 `config(string $module, array $config, Site $site)`、`register/registers(..., Site $site)`：

- `Site` 必填，不能依赖当前上下文；
- `$module` 必须等于 `$site->pluginId`，否则抛 `InvalidArgumentException`（`Support\Features\Site\Concerns\AssertsSiteOwnership`）；
- 同一模块对同一站点重复 `config()` 是浅合并，后写覆盖；
- `registers()` 就是逐个 `register()` 的语法糖：追加 + 同名覆盖（Feed 的 key 是流名、条目是数组；Sitemap 是列表；Search 的条目是 `SearchSource` 实例）；空数组表示不注册任何条目，等价于本站没有该模块的条目。

| 注册表 | 归属限制 | 原因 |
|---|---|---|
| Feed | `$module === $site->pluginId` | 占用 `{prefix}/feed/{name}`，路由名也来自站点 group |
| Sitemap | 同上 | 一个站点一份 `sitemap.xml`，来源只能属于站点所属模块 |
| Search | 同上 | 一个站点一份搜索空间与结果页 |
| UserConfig | 同上 | 认证 guard / 双因素 / 跳转地址按站点派生 |
| Seo | 无断言 | 还接受页面级 `init($module)`，页面可属于非站点所属模块 |
| Sidebar | 见各自文档 | 菜单是站点 + 模块的组合资产 |

## 4. 读取

Feed / Search / Sitemap 的读入口**只传 `Site`**，模块由 `$site->pluginId` 派生（`siteModule($site)`），没有缺省上下文；组件与控制器用 `SiteContext::requireSite()` 或路由默认值取得站点。

选项类读取走统一的 `resolveConfig(Site $site, string $key, $default = null)`：**站点声明（扩展包把站点 `options` 的对应节整节透传进 `config()`）> `sn-support.*` 全局配置 > 默认值**。Feed / Search / Sitemap 各有一份，语义一致；扩展包新增行为参数时不需要自己拼覆盖链。

例外是 `UserConfig::getConfig()`：它接受 `?Site`——无站点上下文时只用 `sn-user.*` 兜底配置（模型加密器、面板等调用方拿不到站点），**绝不跨站点借用数据**。

## 5. 注册时机

每个模块把自己的注册逻辑写成 `SiteService`：

```php
final class RegisterSiteFeed extends SiteService
{
    public function register(Site $site): void { /* Feed::config(..., $site) */ }

    public function finish(): void { /* 所有站点注册完后执行一次 */ }
}
```

`SiteServicePipeline` 外层循环站点、内层循环服务，`finish()` 只跑一次。站点服务在注册期已持有 `$site`，直接读 `$site->getOption()` / `$site->route()`，**不要**为了读配置去 `SiteContext::run()`。

## 6. 各注册表的属性

| 注册表 | 属性 | 形态 |
|---|---|---|
| `FeedRegistry` | `$configs` | module => siteId => options |
| | `$feeds` | module => siteId => name => definition |
| | `$feedRouteNames` | module => siteId => routeName |
| `SearchRegistry` | `$configs` | module => siteId => options |
| | `$sources` | module => siteId => Collection<SearchSource> |
| `SitemapRegistry` | `$configs` | module => siteId => options |
| | `$sources` | module => siteId => list<source> |
| | `$robotsRules` | module => rules（模块级，robots.txt 是全局文件） |
| `UserConfig` | `$configs` | module => siteId => array\|Closure |
| `Seo` | `$configs` | module => siteId => array\|Closure |
| `SidebarMenuRegistry` | `$siteMenus` | module => siteId => Collection<menu> |

## 7. 缓存

带缓存的注册表（Feed / Sitemap）cache key 都包含 `tenant + siteId + host + module`（Feed 另加 feed 名），避免跨租户/站点污染；内容变更后调 `Sitemap::flush($site)` / `Feed::flush()`。

## 8. 站点路由扩展（SiteRouteRegistry）

宿主往站点的前台路由组里追加页面时用 `SiteRoutes`（`Support\Features\Site\SiteRouteRegistry`）：

```php
SiteRoutes::extend('sn-cms', CmsFrontendRoutes::SLOT_AUTH, function (Site $site): void {
    Route::get('aaa/my-posts', AaaMyPostsPage::class)->name('aaa.my-posts');
});
```

- **槽位由模块定义**，support 只按 `module + slot` 存回调，不校验取值：cms 定义 `SLOT_PUBLIC` / `SLOT_GUEST` / `SLOT_AUTH`，shop 定义自己的；
- `apply($module, $site, $slot)` 由拥有路由组的模块在对应 group 内调用，因此 prefix / domain / middleware / 路由名前缀全部继承；
- 回调**每个站点执行一次**，参数是当前 `Site`；
- `extend()` 在 `ModuleRegistry` 之外会立即抛异常，避免拼错模块 id 静默失效；
- `flush()` 只用于测试清理。
