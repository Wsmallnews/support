## Support Package（wsmallnews/support）

`wsmallnews/support` 是 Wsmallnews 生态的基础 Filament 插件包，为 cms、shop、user 等扩展包提供基类、工厂方法与注册表。命名空间根 `Wsmallnews\Support`，视图前缀 `sn-support`，配置 `config/sn-support.php`。

本文件只写**契约与速查**。用法与配置项见包内 `README.md`。

### 不能违反的硬性契约

1. **模型继承 `SupportModel`**，不要直接继承 `Illuminate\Database\Eloquent\Model`。
2. **组件优先用工厂方法**（`FormComponents` / `FilterComponents` / `ActionComponents` / `ColumnComponents`），工厂已应用配置；用了工厂就不要再手写 `disk()` / `visibility()` 等默认值。
3. **scopeable 资源**：模型继承 `SupportModel` 即带 `SiteScope`（记录访问器 + 三类查询入口）；Resource 查询经 `applyScopeToQuery()`（内部 `panelContext()`）；CreatePage 必须 `mutateFormDataBeforeCreate` 合并 scope。
4. **scope_id 可以为 0**（表示该 scope_type 下的全局作用域），查询走 `forScopeable(['scope_type' => ..., 'scope_id' => 0])`，**禁止**手写 `where('scope_id', 0)` 绕过租户规则。
5. **枚举状态字段统一 `FormComponents::enumsToggleButtons()`**，禁止 Radio / Select 做状态字段；状态枚举必须实现 `HasColor` / `HasIcon` / `HasLabel` + `use EnumHelper`。
6. **hardcode 中文 label 禁止**：一律走翻译 key（`<资源>_table.<field>` / `<资源>_form.<field>` / `<资源>_status.<case>`）。
7. **`CanPagination` 已包含 `WithPagination`**，不要再单独 use `WithPagination`。
8. **模块级注册表都用模块 id 作为 key**（= 插件 id，如 `sn-cms`）；同一 id 重复注册视为覆盖。

### 组件工厂

#### FormComponents

```php
use Wsmallnews\Support\Filament\Forms\FormComponents;

FormComponents::mediaImageUpload('avatar', 'avatars');   // Spatie Media Library
FormComponents::mediaFileUpload('attachment', 'files');
FormComponents::plainImageUpload('cover');               // 直接存 disk，路径进字段
FormComponents::plainFileUpload('document');
FormComponents::markdownEditor('description');
FormComponents::richEditor('content');

FormComponents::enumsToggleButtons(XxxStatus::class, 'status');  // 状态字段
FormComponents::orderColumnInput();                              // 排序字段
FormComponents::contentTypeGroup(types: [...], defaultType: ...); // 多态 content 关联
```

- 上传组件自动应用 `form_components.upload`（disk、visibility、maxFiles、maxSize、imagePreviewHeight、panelLayout 等）。
- 编辑器组件自动应用 `form_components.editor`（maxLength、附件盘/可见性/大小、toolbar、浮动工具栏、textColors）。
- `contentTypeGroup()` 内部完成 content 关联的读写，Create/Edit 页面无需任何 content 处理代码；`images` 类型存 JSON 路径数组。

#### FilterComponents

```php
FilterComponents::statusFilter(XxxStatus::class);        // 枚举下拉
FilterComponents::morphFilter(...);                      // 多态 + 关键词
FilterComponents::createUpdateRangeFilter();             // created_at + updated_at
FilterComponents::dateTimeRangeFilter('published_at', '发布时间');
```

#### ActionComponents

```php
ActionComponents::deleteAction();
ActionComponents::editAction();
ActionComponents::toggleAction(Enum::class, 'status');   // 枚举恰好 2 个 case
ActionComponents::switchAction(Enum::class, 'status');   // > 2 个 case，弹 Select
ActionComponents::bulkAction(name: 'bulk_enable', process: fn ($action, $record) => ...);
ActionComponents::recordActions([...]);                  // 按配置决定是否包 ActionGroup
ActionComponents::toolbarActions([...]);                 // 按配置决定是否包 BulkActionGroup
```

`bulkAction()` 已内置逐条 try/catch 与失败计数，闭包内只管单条记录逻辑。

#### ColumnComponents

```php
ColumnComponents::contentColumn('content', '内容');   // Textarea 直显，富文本/Markdown 弹框
ColumnComponents::modelColumn('name', '名称', fn ($record) => $record);
ColumnComponents::relationColumn('user.name', '用户', fn ($record) => $record->user);
ColumnComponents::morphColumn('causer_type', '操作人', fn ($r) => $r->causer, fn ($r) => $r->causer_type, fn ($r) => $r->causer_id);
```

### Filament Resource 五层结构

```
Resources/Xxxs/
├── BaseResource.php        # abstract：本包资源全部默认值，不定义 getPages()
├── XxxResource.php         # final：getPages() + 配置模式入口
├── Pages/                  # List / Create / Edit / (View)
├── Schemas/XxxForm.php     # 静态 configure()
└── Tables/XxxsTable.php    # 静态 configure()
```

- **BaseResource**：`getModel()` 必须走 `Utils::getModel()`（模型可经 `config('包名.models.xxx')` 替换，这是资源能被复用的前提）；标签用 `static::$xxx ?? 翻译key`；`form()` / `table()` 委托给 `Schemas` / `Tables`。
- **XxxResource**：只做 `getPages()` 与 `CanBeConfigured`（`$configurationClass` 与 scope 契约由 `BaseResource` 声明），不定义默认值。自定义资源继承 `BaseResource` 时只需 `use CanBeConfigured;`。
- **Pages**：`$resource` 写死指向 final 类；具体页面用 `CanBeConfigured` 从注册声明取 scope（抽象 Base 不带 scope trait）。
- **继承即自建 Pages**：其他包 `extends BaseResource` 时必须自建 Pages 并把 `$resource` 指向自己的类；不想自建就直接注册 `XxxResource::class` 并用插件 customProperties 覆盖 form / table。

### Table / Form 规范

**Table 方法顺序**（固定）：

```php
return $table
    ->columns([...])
    ->reorderable('order_column', direction: '...')   // 仅含 order_column 的表
    ->defaultSort('...', '...')
    ->searchPlaceholder(__(...))
    ->filtersFormWidth(Width::Medium)
    ->filters([...])
    ->recordActions([...ActionComponents::recordActions([...])])
    ->toolbarActions([...ActionComponents::toolbarActions([...])]);
```

- 列顺序：`id` → 主体识别列 → 业务属性 → 关联/badge → counter → `order_column` → `status` → `published_at` → `created_at` → `updated_at`。
- `id` 列固定 `->label('ID')->searchable()->sortable()->alignCenter()->toggleable()`。
- **status 列必须 `->badge()`**；`order_column` 列不加 `sortable()`。
- filters 顺序：业务筛选 → `...FilterComponents::createUpdateRangeFilter()` → `TrashedFilter::make()`（仅软删除表）。

**Form 规范**：

- **禁止 `Schemas\Components\Flex` 侧边栏布局**；所有字段必须在 Section 内，不裸放顶层。
- 只有一个 Section 时基础字段直接放其中；多个 Section 时 order_column / status 放最上面的业务主体 Section。
- Section 用 `->columns(2)->columnSpanFull()`。

### order_column

| 层 | 规则 |
|---|---|
| 迁移 | `unsignedInteger('order_column')->nullable()` + 索引 |
| 模型 | `use Models\Concerns\HasOrderColumn`：creating 时为空则填 `max + 1`；需要隔离序号覆盖 `modifyOrderColumnQuery()` |
| 表单 | `FormComponents::orderColumnInput()`，保留字段可微调，**不隐藏** |
| 表格 | `->reorderable('order_column', direction: X)` 与 `->defaultSort('order_column', X)` **方向必须一致**，否则拖拽后顺序颠倒 |

默认方向：内容型（新内容优先）`desc`；配置型（顺序稳定）`asc`。改完必须核对前端 Livewire 组件的排序方向。

### 枚举色板

| 语义 | 颜色 | 图标（一律 `Heroicon::Outlined*`） |
|---|---|---|
| 正常 / 已发布 / 上架 | `primary` | `OutlinedCheckCircle` / `OutlinedEye` / `OutlinedArrowUp` |
| 隐藏 / 草稿 | `gray` | `OutlinedEyeSlash` / `OutlinedClipboardDocumentList` |
| 禁用 / 下架 / 拒绝 | `danger` | `OutlinedNoSymbol` / `OutlinedArrowDown` / `OutlinedShieldExclamation` |
| 待审核 | `warning` | `OutlinedDocumentCheck` |

- 相同状态语义 → 相同颜色 + 相同图标。
- 禁止 solid 常量与字符串图标；枚举结构：`enum X: string implements HasColor, HasIcon, HasLabel` + `use EnumHelper`。

### SupportModel 与 SiteScope

```php
class Post extends SupportModel
{
    // siteContext() / panelContext() / forScopeable() / scopeTenant() / getModelLabel()
}
```

- `scope_type` + `scope_id` 由模型 trait `Models\Concerns\SiteScope` 提供（记录访问器 `getScopeable()` / `getScopeType()` / `getScopeId()` 与查询入口）；`scope_id = 0` 表示该类型下的全局作用域（不用 Laravel 原生 morph，正是因为 0 不是可指向的记录）。
- 查询入口按意图分三类：`siteContext($subScope = null)`（前台，从站点派生，无 site 抛异常）/ `panelContext($scopeable)`（面板，必传注册声明 scope）/ `forScopeable($scopeable, withTenant: true)`（显式，记录自身 scope、console/queue、跨 scope 嵌入；父记录已锁死租户的关联查询传 `withTenant: false`）。租户轴：`scopeTenant()` / `forTenant($id)` / `allTenants()` / `tenantAggregate()`。
- Filament 侧：`RequiresConfiguration`（基类契约：未注册时 scope/module 抛可读异常）、`CanBeConfigured`（按注册声明解析 scope）、`HasPanelScopedType`（后台树页面的容器类型解析：scope + 租户只算一次，容器缺失且非管理模式时创建）、`Resources\Concerns\ScopedQueries`（`applyScopeToQuery()`；面板有 tenancy 时租户条件交给 Filament 的全局作用域，无 tenancy 时补 `whereNull(team_id)`）、`Resources\Concerns\Pages\ForwardsResourceScope`（页面把 scope 转发给所属资源）。
- 公用全局资源（member、user）**不需要** scopeable；可能多份存在的资源（post、product、link）才用。
- 资源 scope 配置错误直接抛 `InvalidScopeException`，不回落 `default`。

### 插件配置（CanBeConfigured）

```php
final class PostResource extends BaseResource
{
    use CanBeConfigured;
}
```

`module_id` 由注册插件自动注入（注册即归属），手工声明会被覆盖。

### 站点插件（RegistersSites）

拥有前台站点的插件用 `RegistersSites`，必须先 `site()` 再 `resources()/pages()`，每个站点各生成一份配置：

```php
CmsPlugin::make()
    ->site('main', fn (SiteConfig $site) => $site->prefix('cms'))
    ->resources([PostResource::class, [PostResource::class, ['key' => 'vip', 'scope_suffix' => 'vip']]])
    ->pages([FooterNavigationPage::class]);   // 子 scope 由类的 getScopeSuffix() 自述
```

- 条目三形态：裸类名 / `Class => [overrides]` / `[Class, [overrides]]`。只有元组能表达"同一个类在本站点的第二份实例"，且必须自带 `key` 与 `scope_suffix`。
- 配置 key 按 `panelId-pluginId-siteKey-class` 派生；同一站点内 `(class, key)` 重复抛，不同类可共用 key（Filament 按 `[class][key]` 查找）。`resources()`/`pages()` 多次调用是追加。
- **条目禁止 `scopeable` / `scope_type` / `scope_id`**：pair 只能由站点派生（`{site.scopeType}::{suffix}`），suffix 只负责命名分区。
- **跨资源读取用 `related_scope_suffix`**：条目可配 `['composition' => 'post']`（key 由调用方约定，Page 绑定编排固定用 `composition`）；读取时用「站点基础 scope + 该后缀」，未配置的 key 回退站点基础 scope，后缀未注册时查询为空、不抛异常。
- 站点显式 `scopeable()` 必须带插件 id 前缀；非 aggregate 站点的 `(scope_type, scope_id)` 在 `SiteRegistry` 全局唯一，跨插件重复注册期抛。
- 站点级后台默认值用 `$site->panelDefaults([...])`，优先级 `条目 overrides > 站点 panelDefaults > 模块 panel_register.global_default`；`navigation_group` 多站点时以 site label 兜底。

### 注册表速查（存储 `module => siteId`；读取只传 `Site`，module 由 `$site->pluginId` 派生）

| 能力 | 入口 | 主要方法 |
|---|---|---|
| 内容编排 | `CompositionRegistry` | `register($module, ['type' => ..., 'component' => ...])`、`registerPurposes()`、`getTypeForms()` |
| 搜索 | `Search` | 写 `config($module, $config, $site)` / `registers($module, ...)`；读 `search($query, $site)` / `resolvePage($site)` |
| Sitemap | `Sitemap` | 写 `config($module, ...)` / `registers($module, ...)` / `robots($module)` / `routes($module, $site)`；读 `render($site)` / `flush($site)` |
| Feed | `Feed` | 写 `config($module, ...)` / `register($module, $name, [...], $site)` / `routes($module, $site)`；读 `getFeeds($site)` / `render($site)` / `flush()` |
| SEO | `Seo` | `config($module, [...], $site)` / `init($module)` / 链式 `title()` 等 / `render()` |
| 定时任务 | `ScheduledTask` | `registers($morphType, [...])` / `scheduleRepeater()` |
| 模块身份 | `ModuleRegistry` | `register(new Module(id, namespace, plugin))` / `moduleOf()` / `require()` |

要点：

- 一个站点只有一个模块：写入的 `$module` 必须等于 `$site->pluginId`（`AssertsSiteOwnership`），读取只传 `Site`。
- 注册表都要求模块 id 已登记（`ModuleRegistry`），拼错 id 应当报错而不是静默失效。
- **同一站点同名流重复注册 = 整条覆盖**（Feed 的 name 是 URL 段）。
- 内容变更后主动调 `Sitemap::flush($site)` / `Feed::flush()`（缓存键含租户、站点、请求 host）。
- **Search 来源的 `url` 只能由注册方提供**，默认无链接——前端搜索永不产生后台 panel 地址。
- 相同 key 重复注册 = 覆盖（应用可借此替换包内置来源）。

### Money（金额）

- 存储：数据库一律存**最小货币单位整数**（分），列 `unsignedBigInteger`；JSON 金额明细同样存整数分。
- 输入：`int` = 分；`string|float` = 元（十进制主单位）；`Money` 对象原样透传。
- 运算一律经 `sn_money()`（`MoneyManager`），**禁止**浮点运算后入库；分摊用 `allocate()`（余数分配，总额不丢分）。
- 币种解析链：`config('app.currency')` → `sn-support.currency` → `CNY`；单据表用 `MoneyCast::class.':currency'` 绑定行内币种列。
- 展示只走 `format()` / `symbol()` / `formatParts()`；符号与千分位禁止入库。
- `Features\Currency` 与 `sn_currency()` 已废弃，不要在新代码里使用。

### Livewire 基础设施

- `Livewire\Base`：`$this->transaction(fn () => ...)` 带 Halt 感知；`$this->halt()` 停止并回滚；`getAuthUser()` 直接读站点认证身份（无需 trait）。
- Traits：`HasProperties`、`HasContentType`、`HasAuth`（可 `:auth-user` 注入，trait 方法覆盖 Base 的读取，props 优先、站点兜底）、`CanPagination`（scroll / paginator / manual）、`CanBeContained`、`HasCustomRoutes`（`$customRoutes['detail' => 'posts.show']` + `routeUrl()/routeName()`，调用方注入路由名）、`ScopedBySite`（`#[Locked]` 快照 + 站点派生 + 站点配置读取）。
- `HasPanelScopeable`：面板内嵌组件（Page / Widget / 嵌套组件）用 `#[Locked] ?array $scopeable` 接收宿主传的数据空间；`panelScopeable()` 在缺失时抛 `InvalidScopeException`（面板无 `SiteContext`，不猜 scope）。
- `ScopedBySite` 的分区：`$scopeSuffix` 选择站点已声明的子 scope（null = 站点 base）；类声明了 `getScopeSuffix()` 时实例只能重复该值，传别的直接抛。关联资源用 `$relatedScopeSuffix`（`关联key => suffix`）配 `getRelatedScopeable($key)`：未声明回落站点 base，声明了但 suffix 未注册直接抛。
- `HasScopedType`：类型容器解析（`Categoryable` / `Navigationable` 的底座）。宿主用 `typeScopeable()` 决定数据空间，默认 `getBaseScopeable()`；类型每请求只查一次、不进 Livewire 快照，`scopedType()` 断言容器与行同 scope 后再返回查询条件。
- 组件配置按站点读取：`$this->siteOption($path, $default, $owner = null)`（站点 options > 模块 config > `$default`）。`$owner` 三态：`null` = 组件所属模块（`getOwnerModule()` 经 `ModuleRegistry` 按命名空间反查）、`false` = 不要模块兜底（只认站点值）、字符串 = 指定模块。不存在“消费模块” `:module` 上下文。
- `HasColumns`（`Concerns\HasColumns`）提供响应式列配置，但 HTML 布局仍写在视图里。

### 面板小部件（Filament Widget）

- 面板没有站点上下文（`SiteContext` 只在前台路由组生效），Widget 也不能从记录的 scope 反推站点。站点由渲染它的页面/资源用 `siteId` props 传入。
- 写法：继承 `Filament\Widgets\Base`（已 use `Concerns\ResolvesSite`），或直接 use 该 trait（已有别的基类时，如 cms 导航 Widget）。trait 提供 `#[Locked] public ?string $siteId` + `getSite()`（查 `SiteRegistry`）。
- 宿主在 `getHeaderWidgets()` / `getFooterWidgets()` 里传：`CommentWidget::make(['siteId' => static::getResource()::getSite()?->id()])`，视图写 `<livewire:... :site-id="$siteId" />`。Widget 还要按数据空间查询时，同时传 `'scopeable' => static::getResource()::getScopeable()`（`HasPanelScopeable`），由 Widget 视图继续往下传给内嵌组件。
- 禁止在 Widget 里 `SiteContext::run()` 或读 `SiteUtils`；记录级数据用 `panelContext($scopeable)`，租户由 `scopeTenant()` 收口。`siteId` 必须 `#[Locked]`，否则客户端可把 Widget 指向别的站点。

### 操作者与目标实体接口

偏好、收藏、购物车一类「谁对什么做了什么」的功能，统一用这两个接口取展示数据：

| 接口 | 角色 | 必实现方法 |
|---|---|---|
| `Contracts\HasSnIdentifiable` | 操作者（用户） | `getSnId()` / `getSnName()` / `getSnAvatarUrl()` / `getSnEmail()` |
| `Contracts\HasSnSubject` | 目标实体（内容） | `getSnSubjectId()` / `getSnSubjectTitle()` / `getSnSubjectDescription()` / `getSnSubjectCoverUrl()` |

- `Concerns\UserIdentifiable` 是 `HasSnIdentifiable` 的 Eloquent 默认实现（映射 id / name / avatar_url / email），用户模型直接 use。
- `HasSnSubject` 没有默认 trait，实现类自行编写（只含固有展示数据，不含跳转链接）。
- 跳转链接由调用方以 `href` prop 传入；panel 语境下未传时兜底 `FilamentModelHelper::getUrl()`。

### 多租户

- 中间件 `ResolveSiteContext`（别名 `sn-resolve-site-context`）已注册为全局 Livewire 持久中间件：一遍解析站点与租户（先站点，租户只在 `tenant` 模式的站点上解析）。`tenant_model` 恒有值（默认 `Models\Team`），是否租户由**站点模式**（前台）或**面板 tenancy**（后台）决定，不由配置开关决定。
- 基础租户模型 `Models\Team`（`teams` 表：`name` / `avatar_url` / `slug` / `status` / `options`，实现 `HasAvatar` + `HasName`）；**推荐宿主继承后**把 `tenant_model` 指向子类，再补 `users()` 等业务关系。状态枚举 `Enums\TeamStatus`（`enabled` / `disabled`）。
- 辅助函数：`current_tenant()`（面板取 Filament 租户，前台取 `SiteContext::tenant()`，`?->getKey()` 取 id）/ `is_in_panel()` / `sn_route()`（仅对 `{tenant:slug}` 路由补参数；缺租户直接抛异常，非 slug 绑定也抛异常）。租户查询 scope：`scopeTenant()` 按当前上下文（站点模式 / 面板 tenancy）严格过滤，显式跨租户用 `allTenants()`，指定租户用 `forTenant($id)`。
- 站点设置仓库 `Tenant\Settings\Repositories\DatabaseSettingsRepository`（表 `sn_site_settings`）：行按站点 scope pair + 租户分区（`team_id IS NULL` = 平台行），无站点上下文 fail-closed；宿主 `config/settings.php` 必须注册 `site_database` 仓库（support 启动期校验，缺失直接抛），`SettingsRepositoryGuard::assertRegistered()`。
- `media-library` 的 `media_observer` 必须换成 support 的实现，否则删记录会连带删文件。

### 站点认证（SiteContext）

身份解析收敛到站点上下文，组件不读 `request()->attributes`，也不判断 member/user 分叉：

- **配置**：站点 `options.auth.guard`（回落 `{pluginId}.options.auth.guard`，再兜底 `web`）/ `options.auth.user_type`（回落模块默认，再兜底 `user`）。站点级覆盖写 `options(['auth' => ['guard' => 'admin', 'user_type' => 'user']])`。
- **注册解析器**：`SiteContext::registerAuthUser('member', fn (Site $site, string $guard) => ...)`；每种类型由所属模块注册——user 包注册 `user`（guard 用户），member 包注册 `member`（guard 用户 → `Member::findOrCreate`）。support 只提供注册表。
- **读取**：`getAuthUser()` 返回站点认证主体（`auth.user_type = member` 时是 Member，为 `user` 时是 User）；`getUser()` 始终返回 guard 用户模型；两者都每个请求解析一次（`getAuthUser()` 缓存，`set()` 换站点时失效）；`getGuard()` / `getAuthUserType()` / `option('auth.guard')`。
- **面板 / console** 没有站点：`getAuthUser()` / `getUser()` 返回 `null`，组件通过 `HasAuth` 的 props 接收用户；`this->getAuthUser()` 优先用显式 props，其次站点。
- 站点声明了未注册的 `user_type` 直接抛 `unknownAuthUser`（fail-closed，不静默当成未登录）。
- 中间件 `sn-ensure-user-active`（user 包）与 `sn-ensure-member-active`（member 包）只做「禁用 → 登出」，不解析身份、不写 request 属性。

### 前端 CSS（sn-*）

引入 `resources/css/index.css` 即可，其中：

- `tokens.css`：设计令牌，主题定制唯一入口；几何令牌按 `lg` 翻转，颜色令牌按 `.dark` 翻转。
- `utilities.css`：通用 `sn-*` 类（容器、徽章、按钮、文字、空态、骨架屏等）。
- `content.css`：`sn-prose` 正文排版。
- `filament.css`：`sn-filament-*`，依赖 Filament 内部 DOM，**仅继承 support 的包可用**。

规则：

- 组合式用法：基础类（形状/尺寸）+ 修饰类（颜色/状态），如 `sn-badge sn-badge-sm sn-badge-danger`。
- 区块间距用 `sn-gap` / `sn-margin` / `sn-padded` 家族，卡片级不要写 `gap-4` / `p-4` 这类固定值。
- 页面骨架用 `sn-page` + `sn-content`；全宽色带内的 `container` 内容层必须加 `sn-page-x` 防贴边。
- `sn-container` 是**内容卡片容器**（自带背景/边框/阴影），不是通用布局 div。
- 状态类 `sn-hover` / `sn-link` / `sn-active` 挂在外层 `sn-container` 上才生效于子元素。
- 动态颜色用 `sn_badge_color()` / `sn_text_color()`（返回 `['class' => ..., 'style' => ...]`），不要手拼色值。

### Utils 与辅助函数速查

| 接口 | 用途 |
|---|---|
| `Utils::getConfig()` | 读 `sn-support` 配置 |
| `Utils::getModel($name, $throw = true)` | 读模型类名，`false` 时允许 null |
| `Utils::getContentModel()` / `getCompositionModel()` / `getPageModel()` / `getScheduledTaskModel()` | 各模型类名 |
| `Utils::getFilesystemDisk()` | 磁盘，回落到 Filament 默认盘 |
| `sn_money()` | 金额服务 |
| `sn_route()` | 租户感知路由 |
| `generate_slug()` | 标题转 slug（空结果兜底随机串） |
| `text_highlight($text, $query)` | 关键词高亮（已转义，`<mark>` 包裹） |
| `sn_badge_color()` / `sn_text_color()` | 语义色 → class + style |
| `href_format()` | 生成 href / target / wire:navigate |
| `files_url()` | 文件 URL 解析 |

### 命名空间速查

| 类别 | 命名空间 |
|---|---|
| 组件工厂 | `Wsmallnews\Support\Filament\{Forms,Filters,Actions,Tables}` |
| Resources | `Wsmallnews\Support\Filament\Resources\{ActivityLogs,Compositions,Pages,ScheduledTasks,Tags}` |
| 配置模式 | `Filament\Concerns\{CanBeConfigured,HasMediaFilter}` |
| 模型 / Casts / 枚举 | `Models\` / `Casts\` / `Enums\` |
| 数据与契约 | `Data\` / `Contracts\` |
| 通用 Traits | `Concerns\`（`UserIdentifiable`、`HasColumns`、`Install\`） |
| Livewire | `Livewire\Base`、`Livewire\Concerns\` |
| 功能注册表 | `Features\{Search,Sitemap,Feed,Seo,Composition,Money,Modules}` |
| 门面 | `Facades\{Search,Sitemap,Feed,Seo,CompositionRegistry,ScheduledTask,Support}` |
| 工具 | `Support\Utils`、`Helpers\FilamentModelHelper` |

### 常见错误

- 用工厂方法后又手动 `->disk()` / `->visibility()` —— 重复且会被配置覆盖。
- 用 `where('scope_id', 0)` 查全局作用域 —— 用 `forScopeable(['scope_type' => ..., 'scope_id' => 0])`（同时带租户规则）。
- `Utils` 全部是静态方法，不要 `new Utils`。
- `Utils::getModel()` 默认抛异常，可选模型要传 `false`。
- 覆盖 `ActivityLogs\BaseResource::getEloquentQuery()` —— 除非清楚 causer 上的租户全局作用域逻辑。
- 表格里 `reorderable()` 与 `defaultSort()` 方向不一致 —— 拖拽后顺序颠倒。
