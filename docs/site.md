# Site（站点）

> 站点 = 一份数据空间（scope）+ 一组前台路由（prefix/domain/middleware/route name），在注册期冻结为不可变对象。

## 1. 它是什么，为什么需要

**是什么**：站点是一份数据空间 + 一组前台路由的定义。它不是数据库表，而是注册期构建、请求期解析的配置对象。

**为什么需要**：

- 一套代码服务多个品牌 / 域名 / 栏目（`main`、`campaign`…），数据互相隔离；
- 后台资源按站点复制，前台按路由前缀识别当前站点；
- 多租户与多站点正交：站点决定数据分区，租户决定数据归属。

同一个模块可以在一个面板里声明多个站点，各自拥有独立的 scope 和前台地址；后台组件按站点复制配置，前台请求按路由名解析出当前站点。

## 2. 快速使用

```php
CmsPlugin::make()
    ->site('main', fn (SiteConfig $site) => $site
        ->prefix('cms')                       // 必填
        ->scopeable('sn-cms', 0)              // 可选，缺省按 key 派生
    )
    ->site('campaign', fn (SiteConfig $site) => $site
        ->prefix('cms/campaign')
        ->label('Campaign')
    );
```

## 3. 运行逻辑

1. `site()` 收集参数到 `SiteConfig`（可变）；
2. 面板插件 `register()` 时解析所有站点，`toSite()` 冻结为 `Site`；
3. `SiteRegistry::register()` 做全局去重（id / 前台地址 / scope 三元组）；
4. `app->booted` 后，各模块的 `SiteServicePipeline` 按站点执行服务注册；
5. 前台请求由 `ResolveSiteContext` 中间件一遍解析站点与租户（先按路由名前缀匹配站点，再按站点模式决定是否解析租户），写入 `SiteContext`。

## 4. 数据结构

```php
// Site（不可变）
Site {
    pluginId:        'sn-cms',
    panelId:         'admin',
    key:             'main',
    mode:            SiteMode::Platform,      // tenant / platform / aggregate
    scopeType:       'sn-cms',                // aggregate 为 null
    scopeId:         0,
    tenantType:      null,
    domain:          null,                    // null = 匹配所有域名
    prefix:          'cms',
    middleware:      ['web'],
    routeNamePrefix: 'sn-cms.main.',          // 缺省 "{pluginId}.{key}."
    routable:        true,
    sourceSiteKey:   null,                    // aggregate 才有
    label:           null,
    options:         ['feed' => ['enabled' => false]],
    panelDefaults:   ['navigation_group' => '内容'],
    scopes: [
        'footer' => ['scope_type' => 'sn-cms::footer', 'scope_id' => 0],
    ],
}

// SiteRegistry（全局单例）
SiteRegistry {
    sites: [
        'sn-cms:main' => Site {...},
    ],
    addresses: [                              // "{domain|*}|{prefix}"
        '*|cms' => 'sn-cms:main',
    ],
    scopeables: [                             // 非 aggregate 才占位
        'sn-cms:0' => 'sn-cms:main',
    ],
}

// SiteContext（每请求）
SiteContext {
    site:      Site|null,
    tenant:    Model|null,
    scopeable: ['scope_type' => 'sn-cms', 'scope_id' => 0],
}
```

## 5. 站点与上下文规则

- `SiteConfig::scopeable()` 必须以插件 id 开头（`sn-cms` 或 `sn-cms-*`），站点不能指向别的模块的数据空间；
- 非 aggregate 站点的 `(scope_type, scope_id)` 全局唯一，跨插件也校验；
- aggregate 站点只读，不占 scope，读取来源站点（`sourceSiteKey`）的 scope；
- `$site->getOption()` 合并站点 options 与 `config("{pluginId}.options.*")`；顶层配置不参与；
- `$site->route()` 依赖当前 `current_tenant()`，console 下需 `SiteContext::run($site, ..., $tenant)`。

## 6. 边界与常见坑

- `SiteRegistry` 由面板插件的 `register()` 填充；面板没被解析时站点为空，模块需在 `app->booted` 里确保 `PanelRegistry` 已解析；
- 同一插件多个站点不能共用 scope，重复注册直接抛 `InvalidSiteException`；
- 站点声明完成后不落库，站点本身没有数据表。

## 7. 相关类

| 类 | 职责 |
|---|---|
| `SiteConfig` | 注册期构建器 |
| `Site` | 不可变站点对象 |
| `SiteRegistry` | 全局去重与查询 |
| `SiteContext` | 请求期当前站点/租户 |
| `SiteService` | 单个站点服务的基类 |
| `SiteServicePipeline` | 按站点执行一组服务 |
