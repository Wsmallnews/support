# 内容编排（Composition）

> 数据在 `sn_compositions`，类型与槽位注册在 `CompositionRegistry`，解析与渲染在 `CompositionRenderer`。

## 1. 定位

编排是「页面主体由后台拼装」的能力，分成三个互不依赖的角色：

| 角色 | 位置 | 职责 |
|---|---|---|
| `Composition`（模型） | `Models\Composition` | 一条编排记录：`purpose` + `components`（行式 JSON）+ `options` + 状态 |
| `CompositionRegistry` | `Features\Composition` | 模块注册**内容类型**（可放进槽位的组件）与**用途槽位**（编排放在哪） |
| `CompositionRenderer` | `Features\Composition` | 把 `components` 解析成「行 → 槽位 → 可渲染组件列表」，并处理上下文传递 |

**为什么类型按 module 注册、数据按 scope 存**：类型是代码能力（cms 才有 posts），与站点数据空间无关；编排数据属于某个站点的某个 scope，两者正交，所以注册用 `$module`，查询用 `$scopeable`。

## 2. 快速使用

后台编辑：support 自带 `CompositionResource`（标题、用途槽位、行布局、左右槽位组件；每个组件的 `extras` 表单由类型注册的 `forms` 提供）。

前台渲染页面主体：

```blade
<livewire:sn-support::components.page :slug="$slug" :module="app(\Wsmallnews\Cms\CmsPlugin::class)->getId()" />
```

详情页侧栏（把某个槽位的编排解析出来，`null` 表示没有，视图回退全宽）：

```php
$sidebar = CompositionRenderer::resolveForPurpose(
    'post-sidebar',
    app(CmsPlugin::class)->getId(),
    $scopeable['scope_type'],
    $scopeable['scope_id'],
    ['slug' => $this->slug],
);
```

## 3. 数据结构

`sn_compositions`：`team_id` / `scope_type` / `scope_id` / `purpose`（null = 通用编排，页面可绑定；有值 = 槽位编排）/ `title` / `components` / `options` / `status` / `order_column`。

`components` 是行数组，每行两个槽位：

```json
[
  {
    "layout": "1-2",
    "left": [
      { "type": "posts", "label": "最新", "description": "", "show_header": true, "contained": true,
        "extras": { "categoryIds": [1] } }
    ],
    "right": []
  }
]
```

`layout` 取值：`full`（整行）、`1-2`（窄左宽右）、`2-1`（宽左窄右）。未知值回退 `full`，坏数据仍能渲染。

页面侧：`sn_pages.composition_id` 绑定一条编排；未绑定时回退页面自有 `content`。

## 4. 注册

### 内容类型

```php
CompositionRegistry::registers($module, [[
    'type' => 'posts',
    'label' => __('sn-cms::cms.content_type.posts'),
    'forms' => fn ($fields) => [/* 后台 extras 表单 */],
    'components' => [Posts::class => []],   // 或 [Posts::class]；key 形式可带默认 extras
    'context' => ['post'],                  // 消费的上下文键
    'provides' => fn (array $extras) => ['post' => /* ... */],  // 产出上下文
]]);
```

### 用途槽位

```php
CompositionRegistry::registerPurposes($module, [
    'post-sidebar' => [
        'label' => fn (): string => __('sn-cms::cms.composition_purposes.post_sidebar'),
        'positions' => ['left', 'right'],
        'default' => 'right',
        'layout_mode' => 'rows',            // rows：可左右分栏；stack：侧栏单列
        'context' => function (array $params, array $scopeable): array {
            return ['post' => /* 按 $params['slug'] + $scopeable 查文章，未命中返回 null */];
        },
    ],
]);
```

注册时机在模块的 `Boot\RegisterComposition` / `RegisterCompositionPurposes`（`MODULE_REGISTRARS`，boot 期、与站点无关）。标签一律用**闭包**延迟翻译，避免 boot 顺序竞态。

## 5. 运行逻辑

### `resolveRows($rows, $module, $pageContext = [])`

1. **行**：校验 `layout`（未知 → `full`）；每行新建上下文袋，种子是 `pageContext`。
2. **槽位**：`left` 先解析、`right` 后解析——**左槽的产出会进入右槽可消费的上下文**；行结束丢弃，不跨行污染。
3. **单项**：类型未注册 → 跳过（不报错）；先「消费」上下文（`context` 列出的键，`extras` 里显式给了就不覆盖），再解析组件，最后「产出」（`provides` 结果合并进袋）。
4. **extras 注入**：注册的默认值与行内配置合并，再注入 `componentInfo`（type/label/description/show_header）、`contained`、`embedded = true`。

### `resolveForPurpose($purpose, $module, $scopeType, $scopeId, $params)`

1. 查 `published` + `purpose` + `forScopeable(...)`，`order_column` 倒序取第一条。
2. 用槽位注册的 `context` 提供者把路由参数解析成上下文（`params` + `scopeable` 两个入参）；**值为 null 的键剔除**（表示未命中）。
3. 解析出的 `rows === []` 视为未命中，返回 `null`——否则侧栏会永远空着，而不是回退全宽。
4. `options.position` 归一：槽位声明的 positions → 槽位 default → 标准集（left/right/top/bottom）→ `right`。

## 6. 渲染

`page-content` 组件（`x-sn-support::page-content`）是页面主体的唯一分支点：

1. `rows` 非空 → `<x-sn-support::composition.rows>`；
2. 页面未绑定编排且有自有内容 → `x-sn-support::content`；
3. 否则 → `x-sn-support::empty` 空态。

`composition.rows` 用容器查询断点（`@4xl` 起三列，窄屏堆叠），`layout` 映射到 `col-span`；`composition.block` 渲染可选标题（`show_header` + label/description），然后 `@livewire($component_name, $extras, key(...))`。

## 7. 站点与 scope

- 编排数据按 `scope_type` / `scope_id` 存：后台写入的 scope 来自 `CompositionResource` 的注册声明（宿主站点派生），前台按站点 scope 读取。
- 类型/槽位注册按 `module`，**不随 scope 变化**——同一模块的多个站点共用一套类型。
- 读取入口：`Page::resolveRows()` 内部用 `siteContext()` 取页面；`resolveForPurpose()` 由调用方传显式 scopeable，数据查询走 `forScopeable()`。

## 8. 页面与首页

- **路由页面**：`Page` Livewire 组件按 `slug` + `siteContext()` 查页面；`pageRecord` 注入优先于 slug 查询。
- **首页**：导航节点 `options.is_home = true` → `page_id` → `Page`；链路任一环缺失返回 `null`，首页渲染空态。
- `module` 为空时只走页面自有内容通道，不解析编排（`rows = []`）。

## 9. 扩展与坑

新增内容类型：`register` 一个 type（`forms` + `components`），组件必须能吃 `extras`（`embedded` / `contained` / `componentInfo` 是通用键）。

新增用途槽位：`registerPurposes` + 调用方 `resolveForPurpose` + 视图按返回的 `position` 放置。

常见坑：

- 切换 `purpose` / `position` 会重置行布局（`layout_mode` 变化时表单会同步 rows）；
- 空编排返回的是 `null`，不是空数组——调用方必须判空后回退；
- `context` 的消费键名必须与别处 `provides` 的产出键一致，写错会静默拿不到值；
- `components` 里 key 形式（`Component::class => [...]`）带的是**默认 extras**，行内配置会覆盖它；
- 注册标签用闭包，别在注册期就 `__()`（翻译上下文可能还未就绪）。
