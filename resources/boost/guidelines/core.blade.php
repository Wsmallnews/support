## Support Package（wsmallnews/support）

`wsmallnews/support` 是 Wsmallnews 生态的基础 Filament 插件包，为其他扩展包（cms、user、category、comment 等）提供可复用的基类、工厂方法和工具。命名空间根为 `Wsmallnews\Support`，Blade 视图前缀为 `sn-support`，配置文件为 `config/sn-support.php`。

### 组件工厂

项目提供了三类工厂类，封装了通用配置，后续会持续扩充。**优先使用工厂方法而非手动实例化 Filament 组件**，工厂会自动应用 `config('sn-support.form_components')` 中的默认配置。

#### FormComponents

```php
use Wsmallnews\Support\Filament\Forms\FormComponents;

// Spatie Media Library 图片/文件上传
FormComponents::mediaImageUpload('avatar', 'avatars');
FormComponents::mediaFileUpload('attachment', 'documents');

// 直接存储到 disk 图片/文件上传
FormComponents::plainImageUpload('cover');
FormComponents::plainFileUpload('report');

// Markdown / 富文本编辑器
FormComponents::markdownEditor('description');
FormComponents::richEditor('content');
```

所有上传组件自动应用：`disk`（取自 `filesystem_disk` 或 Filament 默认盘）、`visibility`、`downloadable`、`openable`、`reorderable`、`appendFiles`、`maxFiles`、`maxSize`、`imagePreviewHeight`。

编辑器组件自动应用：`fileAttachmentsDisk`、`fileAttachmentsVisibility`、`fileAttachmentsMaxSize`、`maxLength`，以及 Markdown/RichText 各自的 `toolbarButtons` 等。

#### FilterComponents

```php
use Wsmallnews\Support\Filament\Filters\FilterComponents;

// 获取 created_at + updated_at 时间区间筛选器
FilterComponents::createUpdateRangeFilter();

// 单个时间区间筛选器
FilterComponents::dateTimeRangeFilter('published_at', '发布时间');
```

#### ActionComponents

```php
use Wsmallnews\Support\Filament\Actions\ActionComponents;

// 自定义标准操作，可以用在非 filament table 页面
ActionComponents::deleteAction();
ActionComponents::editAction();

// 二态切换（枚举须恰好 2 个 case）
ActionComponents::toggleAction(MemberStatus::class, 'status');

// 多态切换（枚举 > 2 个 case，弹出 Select 选择）
ActionComponents::switchAction(ProductStatus::class, 'status');

// 自动错误处理的批量操作
ActionComponents::bulkAction(name: 'bulk_enable', process: function ($action, $record) {
    $record->update(['status' => MemberStatus::Active]);
});

// 包裹 record/toolbar actions（根据配置决定是否用 ActionGroup 包裹）
ActionComponents::recordActions([ViewAction::make(), DeleteAction::make()]);
ActionComponents::toolbarActions([DeleteBulkAction::make()]);
```

#### ColumnComponents

`Wsmallnews\Support\Filament\Tables\ColumnComponents` 提供表格列工厂，用于展示关联模型信息：

```php
use Wsmallnews\Support\Filament\Tables\ColumnComponents;

// 多态关联列（左侧图片 + 右侧标题/描述 + 类型 badge）
ColumnComponents::morphColumn(
    'causer_type',                      // 列名（必须模态类型字段）
    '操作人',                           // 标签
    fn ($record) => $record->causer,    // 获取关联模型
    fn ($record) => $record->causer_type, // 获取多态类型
    fn ($record) => $record->causer_id,   // 获取多态 ID
);

// 普通关联列（无类型 badge）
ColumnComponents::relationColumn(
    'user.name',
    '关联用户',
    fn ($record) => $record->user,
);

// 模型列（简单展示，无多态/关联逻辑）
ColumnComponents::modelColumn('name', '名称', fn ($record) => $record);

// 内容列（Textarea 直接展示，Richtext/Markdown 点击弹框）
ColumnComponents::contentColumn('content', '内容');
```

### SupportModel 基类

`Wsmallnews\Support\Models\SupportModel` 是所有 support 包模型的基类，提供 scopeable 和多租户感知：

```php
use Wsmallnews\Support\Models\SupportModel;

class Post extends SupportModel
{
    // 自动获得：
    // scopeTenant()    — 按当前租户过滤
    // scopeSnScope()   — 组合 scopeable + tenant 过滤
    // getModelLabel()  — 默认返回类名
}
```

各扩展包的模型应继承 `SupportModel`（如 Post、Category、Comment、Member、Product 等），而非直接继承 Laravel 的 `Model`。

### Filament 插件系统

support 提供了一套可配置的 Filament 插件架构，让扩展包的 Resource/Page 可通过配置覆盖：

```php
use Wsmallnews\Support\Filament\Concerns\RegistersConfigurable;

class CmsPlugin implements Plugin
{
    use RegistersConfigurable;

    public function register(Panel $panel): void
    {
        $this->registerConfigurableResources($panel);
        $this->registerConfigurablePages($panel);
    }
}
```

Resource 使用 `CanBeConfigured` + `ResourceConfiguration`：

```php
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Resources\ResourceConfiguration;

final class PostResource extends BaseResource
{
    use CanBeConfigured;

    protected static ?string $configurationClass = ResourceConfiguration::class;

    public static function getEssentialsPlugin(): ?CmsPlugin
    {
        return CmsPlugin::get();
    }
}
```

Page 使用 `CanBeConfigured` + `PageConfiguration`：

```php
use Wsmallnews\Support\Filament\Concerns\CanBeConfigured;
use Wsmallnews\Support\Filament\Pages\PageConfiguration;

class CategoryPage extends Base
{
    use CanBeConfigured;

    protected static ?string $configurationClass = PageConfiguration::class;
}
```

### ScheduledTasks 资源

继承 `Wsmallnews\Support\Filament\Resources\ScheduledTasks\BaseResource` 快速实现定时任务管理：

```php
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\BaseResource;

class ScheduledTaskResource extends BaseResource
{
    // BaseResource 已提供：
    // - 列表页、查看页
    // - table() 配置（id、schedulable 多态列、action、status、scheduled_at、executed_at 等）
    // - infolist() 配置（Tabs: Overview/Payload/Raw Data）
    // - 图标、slug、导航排序、翻译标签
}
```

提供可配置的具体实现 `ScheduledTaskResource`，支持通过插件配置覆盖：

```php
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\ScheduledTaskResource;

// 在 PanelProvider 中直接注册
$panel->resources([ScheduledTaskResource::class]);
```

### 定时调度任务

#### Facade 注册

```php
use Wsmallnews\Support\Facades\ScheduledTask;

// 在 ServiceProvider 中注册可调度的动作
ScheduledTask::registers('sn_post', [
    'publish' => ['label' => '发布', 'handler' => PublishHandler::class],
    'unpublish' => ['label' => '下架', 'handler' => UnpublishHandler::class],
]);

// 在表单中嵌入调度器
ScheduledTask::scheduleRepeater('sn_post');
```

#### Resource & Widget

```php
// 查看页嵌入定时任务 Widget
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Widgets\ScheduledTasks as ScheduledTasksWidget;

ScheduledTasksWidget::make()

// 表格行操作：查看关联的定时任务
use Wsmallnews\Support\Filament\Resources\ScheduledTasks\Concerns\ViewScheduledTasksAction;

ViewScheduledTasksAction::make()
```

### 异常体系

所有扩展包的异常应继承 `SupportException`：

```php
use Wsmallnews\Support\Exceptions\SupportException;

class CmsException extends SupportException {}
class CommentException extends SupportException {}
class ProductException extends SupportException {}
```

`InvalidScopeException` 用于 Scopeable 配置错误时抛出。

### 枚举工具

`EnumHelper` trait 为枚举提供 `getLabel()`、`getColor()`、`getIcon()` 等默认实现：

```php
use Wsmallnews\Support\Enums\Traits\EnumHelper;

enum PostStatus: string implements HasColor, HasIcon, HasLabel
{
    use EnumHelper;

    case Draft = 'draft';
    case Published = 'published';
}
```

### Blade 组件

```blade
{{-- 内容展示（根据 content_type 自动选择渲染方式） --}}
<x-sn-support::content :content-type="$contentType" :content="$content" />

{{-- 折叠内容（支持展开/收起） --}}
<x-sn-support::collapse-content :content-type="$contentType" :content="$content" />

{{-- 图片灯箱 --}}
<x-sn-support::lightbox class="w-full" :galleries="$galleries" thumb-class="size-20" />
```

### Scopeable 系统

不使用 Laravel 原生多态，因为 `scope_id` 可以为 `0`（表示该 scope_type 下的全局作用域）。

**为什么不用 `morphs`：** Laravel 多态 `*_type='post'` + `*_id=0` 会尝试查找 ID 为 0 的 Post 记录（不存在）。而我们的 `scope_id = 0` 语义是"适用于该类型的所有记录"，不是指向某个具体 ID 的记录。

**使用场景：**
- Comment 包：评论同时给所有 post 和 article 提供支持 → `scope_type='post', scope_id=0` / `scope_type='article', scope_id=0`
- Comment 包：给某门店商品评论 → `scope_type='store', scope_id=<store_id>`

#### Model Trait

```php
use Wsmallnews\Support\Models\Concerns\Scopeable;

class Comment extends Model
{
    use Scopeable;

    // 自动获得：
    // $model->getScopeable();    // ['scope_type' => '...', 'scope_id' => 0]
    // $model->getScopeType();    // 'post'
    // $model->getScopeId();      // 0

    // 查询作用域：
    // Comment::scopeType('post')           ->where('scope_type', 'post')
    // Comment::scopeId(0)                  ->whereIn('scope_id', [0])
    // Comment::scopeable('post', 0)        ->where('scope_type', 'post')->whereIn('scope_id', [0])
}
```

#### ScopeableContext 值对象

```php
use Wsmallnews\Support\Data\ScopeableContext;

// 手动创建
$context = new ScopeableContext('post', 0);
$context->isGlobal(); // true (scopeId === 0)

// 从数组或配置创建
ScopeableContext::fromArray(['scope_type' => 'store', 'scope_id' => 5]);
ScopeableContext::fromConfig('sn-cms.scopeable');

// 辅助函数
scopeable_context(['scope_type' => 'post', 'scope_id' => 0]);
scopeable_query($query, ['scope_type' => 'post', 'scope_id' => 0]);
```

#### Filament 层面的 Scopeable

Filament Resources 使用 `HasScopeableProperties` concern：

- `Wsmallnews\Support\Filament\Concerns\HasScopeableProperties` — 提供静态的 `scopeType()` / `getScopeType()` 和 `scopeId()` / `getScopeId()`
- `Wsmallnews\Support\Filament\Resources\Concerns\Scopeable` — Resource 级别的 `applyScopeableToQuery()` 自动对 Eloquent 查询应用 scope 过滤
- `Wsmallnews\Support\Filament\Pages\Concerns\Scopeable` — Page 级别的 scope 支持

### SN 身份与实体接口

为 preference 等扩展包提供统一的"操作者"（谁）和"目标实体"（什么）数据抽象。Blade 组件通过这两个接口获取展示数据。

#### HasSnIdentifiable（身份/操作者接口）

`Wsmallnews\Support\Contracts\HasSnIdentifiable` — 操作者侧（用户）的标准接口，preferencer 模型必须实现：

```php
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Illuminate\Support\HtmlString;

// 接口方法：
getSnId(): int;                                          // 操作者 ID
getSnName(): string | HtmlString | null;                 // 操作者名称
getSnAvatarUrl(): string | HtmlString | null;            // 头像 URL
getSnEmail(): string | HtmlString | null;                // 邮箱
```

#### UserIdentifiable trait

`Wsmallnews\Support\Concerns\UserIdentifiable` 为 `HasSnIdentifiable` 提供基于 Eloquent 属性的默认实现，自动映射 `$this->id`、`$this->name`、`$this->avatar_url`、`$this->email`：

```php
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Concerns\UserIdentifiable;

class User extends Authenticatable implements HasSnIdentifiable
{
    use UserIdentifiable;
}
```

#### HasSnSubject（实体/目标接口）

`Wsmallnews\Support\Contracts\HasSnSubject` — 目标侧（内容实体）的标准接口，preferenceable 模型必须实现：

```php
use Wsmallnews\Support\Contracts\HasSnSubject;
use Illuminate\Support\HtmlString;

// 接口方法：
getSnSubjectId(): int;                                   // 实体 ID
getSnSubjectTitle(): string | HtmlString | null;          // 标题
getSnSubjectDescription(): string | HtmlString | null;    // 描述
getSnSubjectCoverUrl(): string | HtmlString | null;       // 封面图 URL
```

`HasSnSubject` 没有默认 trait，每个实现类需自行实现所有方法（契约只包含固有展示数据，不含跳转链接）：

```php
use Wsmallnews\Support\Contracts\HasSnSubject;

class Post extends SupportModel implements HasSnSubject
{
    public function getSnSubjectId(): int { return $this->id; }
    public function getSnSubjectTitle(): string | HtmlString | null { return $this->title; }
    public function getSnSubjectDescription(): string | HtmlString | null { return $this->description; }
    public function getSnSubjectCoverUrl(): string | HtmlString | null { return $this->getFirstMediaUrl('post_image'); }
}
```

#### 接口对比

| 接口 | 用途 | 对应 trait | href 方法 |
|---|---|---|---|
| `HasSnIdentifiable` | 操作者/用户 | `UserIdentifiable` | 无（链接由调用方传入） |
| `HasSnSubject` | 目标/内容实体 | 无默认 trait | 无（链接由调用方传入） |

两个接口只包含固有展示数据，跳转链接由调用方在 Blade 组件上传入 `href` prop（string|Closure），未传时渲染为普通元素并分发 Livewire 事件；panel 语境（`is_in_panel()`）下未传时组件自动兜底 `FilamentModelHelper::getUrl()`（后台资源链接）。

### 自定义表单字段

@verbatim
<code-snippet name="BoxRepeater 用法" lang="php">
use Wsmallnews\Support\Filament\Forms\Fields\BoxRepeater;

BoxRepeater::make('items')
    ->columns(3)
    ->columnWidths(['name' => '200px', 'price' => '150px'])
    ->headers(['name' => '商品名称'])
    ->schema([
        TextInput::make('name'),
        TextInput::make('price')->numeric(),
    ])
    ->withoutHeader()      // 隐藏表头
    ->isFusionLayout()     // 融合布局（自动隐藏表头）
    ->hideLabels();        // 隐藏字段标签
</code-snippet>

<code-snippet name="Arrange 用法" lang="php">
use Wsmallnews\Support\Filament\Forms\Fields\Arrange;

Arrange::make('categories')
    ->relationships([
        'arranges' => 'categories',       // 一级排列关联
        'recursions' => 'children',       // 二级递归子关联
    ])
    ->tableFields([
        TextInput::make('name'),
        TextInput::make('sort')->numeric(),
    ]);
// 注意：必须同时设置 relationships() 和 tableFields()，否则无法保存和回显
</code-snippet>

<code-snippet name="DistrictSelect 用法" lang="php">
use Wsmallnews\Support\Filament\Forms\Fields\DistrictSelect;

DistrictSelect::make('district')
    // 自动关联 province_name/id, city_name/id, district_name/id 字段
    // 确保模型中存在这些字段，否则 state 回显会静默失败
</code-snippet>
@endverbatim

### Activity Logs 资源

继承 `Wsmallnews\Support\Filament\Resources\ActivityLogs\BaseResource` 快速实现日志功能：

```php
use Wsmallnews\Support\Filament\Resources\ActivityLogs\BaseResource;

class ActivityLogResource extends BaseResource
{
    // BaseResource 已提供：
    // - 列表页、查看页、导出、时间线
    // - getEloquentQuery() 按 log_name 过滤
    // - 图标、slug、导航排序、翻译标签
}
```

模型实现接口可自定义日志展示：

- `Wsmallnews\Support\Contracts\HasModelLabel` — 自定义模型标签（`static getModelLabel(): string`），用于活动日志类型下拉选项的标签解析等

### Tags 资源

继承 `Wsmallnews\Support\Filament\Resources\Tags\BaseResource`：

```php
use Wsmallnews\Support\Filament\Resources\Tags\BaseResource;

class TagResource extends BaseResource
{
    public static function getTagType(): string
    {
        return 'article'; // 标签类型过滤
    }
}
```

### Livewire 基础设施

#### Base 组件

`Wsmallnews\Support\Livewire\Base` 继承 Livewire 的 `Component`，提供带 Halt 感知的数据库事务：

```php
$this->transaction(function () {
    // 数据库操作
});
$this->halt(); // 停止执行，回滚事务
$this->halt($shouldRollbackDatabaseTransaction: true);
```

#### 可用 Traits

| Trait | 提供 |
|---|---|
| `HasProperties` | `$properties` 数组 + `getProperty()`/`setProperty()` |
| `HasContentType` | `$contentType` 属性（Textarea/Richtext/Markdown 枚举） |
| `HasAuth` | `$user` 属性，`authUser()`/`hasAuthUser()` |
| `CanPagination` | **已包含 `WithPagination`**，三种分页模式（scroll/paginator/manual），`withPagination($builder)` |
| `CanBeContained` | `$contained` 布尔属性控制布局 |
| `Scopeable` | `#[Locked]` `$scopeType`/`$scopeId`，`getScopeable()` |

**注意：** `CanPagination` 已经 use 了 Livewire 的 `WithPagination`，**不要再单独 use `WithPagination`**。

#### HasColumns（响应式列配置）

`Wsmallnews\Support\Concerns\HasColumns` 提供响应式断点列配置：

```php
use Wsmallnews\Support\Concerns\HasColumns;

// 设置列数
$this->columns(2);                        // 所有断点默认 2 列
$this->columns(['default' => 1, 'lg' => 3]); // 按断点设置

// 获取列配置
$this->getColumns();       // 获取完整配置数组
$this->getColumns('lg');   // 获取指定断点的列数
$this->getColumnsConfig(); // 获取带默认值的完整配置
```


#### HasMediaFilter（媒体筛选）

`Wsmallnews\Support\Filament\Concerns\HasMediaFilter` 用于自定义媒体集合的筛选逻辑：

```php
use Wsmallnews\Support\Filament\Concerns\HasMediaFilter;

$this->filterMediaUsing(function (Collection $media): Collection {
    return $media->filter(fn ($item) => $item->getCustomProperty('featured'));
});

$this->filterMedia($media);  // 应用筛选
$this->hasMediaFilter();     // 检查是否设置了筛选回调
```

### 多租户

#### 中间件

`Wsmallnews\Support\Http\Middleware\IdentifyTenant` 已注册为全局 Livewire 持久中间件。从路由的 `tenant` 参数按 `slug` 字段解析租户模型，设置请求属性 `has_tenancy` 和 `current_tenant`。

#### 辅助函数

```php
has_tenancy();           // 是否有租户
current_tenant();        // 当前租户 Model（自动判断前端/后台）
frontend_has_tenancy();  // 前端是否有租户
frontend_current_tenant(); // 前端当前租户
is_in_panel();           // 是否在 Filament 后台
sn_route('page.show');   // 租户感知路由（自动添加 tenant 参数）
```

配置 `sn-support.tenant_model` 为租户 Model 类来启用多租户；设为 `null` 则禁用。

### Eloquent Casts

@verbatim
<code-snippet name="Eloquent Casts" lang="php">
use Wsmallnews\Support\Casts\MoneyCast;
use Wsmallnews\Support\Casts\CounterCast;
use Wsmallnews\Support\Casts\ImplodeCast;

class Product extends Model
{
    protected $casts = [
        'price' => MoneyCast::class,                // cknow/money 金额转换
        'price' => MoneyCast::class . ':currency',  // 指定货币字段名
        'counters' => CounterCast::class,           // JSON 计数器，未设置的 key 默认返回 0
        'tags' => ImplodeCast::class,               // 逗号分隔字符串 ↔ 数组
    ];
}
</code-snippet>
@endverbatim

### Rocket 数据管道

`Wsmallnews\Support\Rocket` 提供 params → radars → payloads 三层数据处理管道：

```php
$rocket = new Rocket();
$rocket->setParams(['user_id' => 1]);
$rocket->setRadar('key', 'value');
$rocket->setPayloads(['result' => $data]);

$rocket->getParam('user_id');
$rocket->getRadar('key');
$rocket->getPayloads(); // Collection
```

### 通用全局搜索（Features/Search）

核心在 `Wsmallnews\Support\Features\Search`（`SearchRegistry` 单例 + `Search` 门面），各扩展包在 `packageBooted()` 中注册可搜索来源：

@verbatim
```php
use Wsmallnews\Support\Facades\Search;

// 搜索名 = 插件 ID；模块是否启用由包的配置决定（未开启则不注册来源，前端也不渲染搜索框）
if (Utils::getConfig('search.enabled', true)) {
    Search::engine(app(CmsPlugin::class)->getId(), Utils::getConfig('search.engine'))   // null 走全局兜底
        ->registers(app(CmsPlugin::class)->getId(), [
            [
                'key' => 'post',
                'model' => Utils::getPostModel(),      // 支持 morph 别名
                'group' => '图文',                     // 默认模型 label
                'query' => fn ($query) => $query->published(),   // LIKE 引擎过滤
                'scopeable' => Utils::getScopeable(),
                'url' => fn ($record) => Utils::route('posts.show', $record),  // 仅注册方提供，默认无链接
            ],
        ]);
}

// 聚合包（如 shop）需要多来源时，在自己的 ServiceProvider 中重复注册所需来源，
// 并可自定义 url 等选项（指向 shop 自己的详情页）
Search::registers('sn-shop', [
    ['key' => 'post', 'model' => CmsUtils::getPostModel(), 'url' => fn ($record) => shop_post_route($record)],
    ['key' => 'product', 'model' => ProductUtils::getProductModel(), 'url' => fn ($record) => shop_product_route($record)],
]);

// 查询：返回 Collection<string $group, Collection<SearchResult>>
Search::search('关键词');                // 所有已注册模块来源合并（union）
Search::search('关键词', 'sn-cms');      // 仅指定模块；未知搜索名抛 SupportException
```
@endverbatim

- **来源选项**：`key`、`model`（必填）、`group`、`fields`（默认 `resolveKeywordSearchFields()` 并剔除含 `.` 的关联字段）、`limit`、`sort`、`query`（LIKE）、`scout`（Scout 索引过滤，此时 query/scopeable/fields 不生效）、`scopeable`、`title`/`description`/`cover`/`badge`（默认取 `HasSnSubject` 固有数据）、`url`（默认无链接，前端搜索永不产生 panel 地址）、`visible`、`results`（完全自定义结果，绕过引擎）。
- **引擎（模块级）**：`database`（默认，WHERE LIKE，空白拆词多词 AND、字段间 OR）与 `scout`（`Engines\Engine` 接口扩展）。`Search::engine($search, $engine)` 按模块声明引擎（null 移除声明恢复全局兜底；可链式、与注册顺序无关、后调用覆盖），模块内所有来源统一；未声明的模块查询时走全局兜底 `config('sn-support.search.engine')`。`scout` 需模型 use `Laravel\Scout\Searchable`（PHP trait 无法条件引入，包内模型不 use，项目可通过模型配置替换子类），未安装 scout 时抛带安装指引的 `SupportException`。
- **启用开关（注册入口门控）**：模块是否启用由各扩展包在 `packageBooted()` 用配置自行判断——未开启则**不调用 engine/registers**（来源不进注册表，前端也不渲染搜索框），如 cms 的 `if (Utils::getConfig('search.enabled', true)) { Search::engine(...)->registers(...); }`，视图侧用同一配置判断是否渲染搜索框。cms 的配置节为 `sn-cms.search.enabled` / `sn-cms.search.engine`。
- **项目启用 scout 的步骤**：① `composer require laravel/scout`；② 给模型 use `Searchable` —— 包内模型用子类替换：新建 `App\Models\Cms\Post extends \Wsmallnews\Cms\Models\Post`（use `Searchable`）并把 `config('sn-cms.models.post')` 指向它，业务代码全部经 `Utils::getPostModel()` 解析无需改动；③ 把 `config('sn-support.search.engine')` 设为 `'scout'`（注册时未显式指定引擎的来源全部切换）。Meilisearch/Algolia 等外部引擎需先 `scout:import` 建索引，collection/database 驱动无需。
- **相同 key 重复注册视为覆盖**（应用可借此覆盖包内置来源）。
- **前端组件**：`<livewire:sn-support-components-search :search-key="app(CmsPlugin::class)->getId()" placeholder="搜索…" :limit="5" />`（`search-key` 绑定模块，null 搜索所有已启用模块），视图 `sn-support::livewire.components.search`，配置在 `config/sn-support.php` 的 `search` 节（`engine`、`results_limit`、`split_terms`、`case_insensitive`、`debounce`）。

### Utils 工具类

`Wsmallnews\Support\Support\Utils` — 全部为静态方法：

| 方法 | 说明 |
|---|---|
| `getConfig('key', $default)` | 读取 `sn-support` 配置（dot notation） |
| `getModel('name', $shouldException)` | 获取配置中的模型类名，第二个参数 `false` 时不抛异常 |
| `getTenantModel()` | 获取租户模型类名 |
| `getContentModel()` | 获取 Content 模型类名 |
| `getScheduledTaskModel()` | 获取 ScheduledTask 模型类名 |
| `isTenancyEnabled()` | 判断多租户是否启用 |
| `getFilesystemDisk()` | 获取文件系统磁盘（回退到 Filament 默认盘） |
| `getScopeFromConfig('sn-cms.scopeable')` | 从配置创建 ScopeableContext |
| `getSchedulerConfig('key', $default)` | 读取定时调度配置 |

### 关键辅助函数

| 函数 | 说明 |
|---|---|
| `get_sn($id, $type)` | 生成唯一编号（时间戳 + 随机数 + ID） |
| `client_unique()` | 获取客户端唯一标识（基于 URL + IP + UserAgent 的 MD5） |
| `db_listen()` | 开启数据库查询监听（调试用，直接 echo SQL） |
| `sn_currency()` | 获取自定义 Currency 操作类实例 |
| `exception_log($exception, $name, $message)` | 格式化异常日志（含 Message、File、Trace） |
| `through_cache($key, $callback, $store, $is_force, $ttl)` | 缓存穿透模式，支持指定 store、强制刷新、TTL |
| `href_format($url, $newTab, $spaMode)` | 生成带 wire:navigate 的链接 HTML |
| `files_url($files, $disk)` | 解析文件 URL（自动判断 http/data 开头 vs 相对路径） |
| `filter_richeditor($content)` | 去除富文本中包裹图片的 anchor 标签 |
| `frontend_has_tenancy()` | 前端是否有租户（从 request attributes 读取） |
| `frontend_current_tenant()` | 前端当前租户 Model |
| `has_tenancy()` | 全局是否有租户（自动判断前端/后台） |
| `current_tenant()` | 全局当前租户 Model（自动判断前端/后台） |
| `get_tenancy_scope_name($panel)` | 获取租户作用域名称 |
| `is_in_panel()` | 当前是否在 Filament 后台面板 |
| `tree_to_flatten($tree)` | 递归将树结构扁平化为一维集合 |
| `sn_route($name, $params, $absolute)` | 租户感知路由，多租户启用时自动添加 tenant 参数 |
| `remove_query_param_from_url($url, $keys)` | 移除 URL 中的指定 query 参数 |
| `scopeable_context($input)` | 创建 ScopeableContext（支持数组、实例、配置 key） |
| `scopeable_query($query, $scope)` | 对查询应用 scope 过滤 |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| FormComponents 工厂 | `Wsmallnews\Support\Filament\Forms\FormComponents` |
| FilterComponents 工厂 | `Wsmallnews\Support\Filament\Filters\FilterComponents` |
| ActionComponents 工厂 | `Wsmallnews\Support\Filament\Actions\ActionComponents` |
| ColumnComponents 工厂 | `Wsmallnews\Support\Filament\Tables\ColumnComponents` |
| 自定义表单字段 | `Wsmallnews\Support\Filament\Forms\Fields\` |
| Activity Logs 资源 | `Wsmallnews\Support\Filament\Resources\ActivityLogs\` |
| Tags 资源 | `Wsmallnews\Support\Filament\Resources\Tags\` |
| ScheduledTasks 资源 | `Wsmallnews\Support\Filament\Resources\ScheduledTasks\` |
| SupportModel 基类 | `Wsmallnews\Support\Models\SupportModel` |
| Livewire Base | `Wsmallnews\Support\Livewire\Base` |
| Livewire Traits | `Wsmallnews\Support\Livewire\Concerns\` |
| Model Traits | `Wsmallnews\Support\Models\Concerns\` |
| Models | `Wsmallnews\Support\Models\` |
| Casts | `Wsmallnews\Support\Casts\` |
| Enums | `Wsmallnews\Support\Enums\` |
| EnumHelper | `Wsmallnews\Support\Enums\Traits\EnumHelper` |
| Data 对象 | `Wsmallnews\Support\Data\` |
| Contracts（接口） | `Wsmallnews\Support\Contracts\` |
| Contracts - 活动日志 | `Wsmallnews\Support\Contracts\ActivityLogs\` |
| 通用 Traits | `Wsmallnews\Support\Concerns\` |
| UserIdentifiable | `Wsmallnews\Support\Concerns\UserIdentifiable` |
| HasColumns | `Wsmallnews\Support\Concerns\HasColumns` |
| Plugin 自定义属性 | `Wsmallnews\Support\Concerns\Plugin\` |
| Resource 自定义属性 | `Wsmallnews\Support\Concerns\Resource\` |
| 安装工具 | `Wsmallnews\Support\Concerns\Install\` |
| Filament 通用 | `Wsmallnews\Support\Filament\Concerns\` |
| FilamentModelHelper | `Wsmallnews\Support\Helpers\FilamentModelHelper` |
| Utils | `Wsmallnews\Support\Support\Utils` |
| Facade | `Wsmallnews\Support\Facades\Support` |
| ScheduledTask Facade | `Wsmallnews\Support\Facades\ScheduledTask` |
| 中间件 | `Wsmallnews\Support\Http\Middleware\` |

### 常见错误

- **使用 `FormComponents::` 工厂方法时不要再手动设置 disk/visibility 等**——工厂已从 config 读取默认值。
- **`BoxRepeater` 需要调用 `->columns()` 和 `->columnWidths()`** 才能正确显示表头宽度。
- **`Arrange` 必须同时设置 `relationships()` 和 `tableFields()`**，缺一不可。
- **`DistrictSelect` 要求模型中存在 `province_name/id`、`city_name/id`、`district_name/id` 字段**，否则 state 回显会静默失败。
- **`CanPagination` 已包含 `WithPagination`**，不要再单独 use `WithPagination`。
- **使用 `scope_id = 0` 时不要用 `where('scope_id', 0)` 直接查询**——用 Model trait 提供的 `scopeScopeId(0)`，它内部使用 `whereIn`。
- **`Utils` 所有方法都是静态的**——`Utils::getConfig()` 而非 `(new Utils)->getConfig()`。
- **`Utils::getModel()` 默认会抛异常**，传递 `false` 作为第二个参数以允许返回 `null`。
- **扩展 `ActivityLogs\BaseResource` 时不要覆盖 `getEloquentQuery()`**——除非你理解 causer 上移除租户全局作用域的逻辑。
