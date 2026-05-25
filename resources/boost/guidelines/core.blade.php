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

// 本地图片/文件上传
FormComponents::localImageUpload('cover');
FormComponents::localFileUpload('report');

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

ActionComponents::deleteAction();
ActionComponents::editAction();
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
    // - getEloquentQuery() 按 log_name 过滤，预加载 causer（移除租户全局作用域）和 subject
    // - 图标、slug、导航排序、翻译标签
}
```

模型实现接口可自定义日志展示：

- `Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogTitle` — 自定义日志标题
- `Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogUrl` — 自定义查看链接

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

### Utils 工具类

`Wsmallnews\Support\Support\Utils` — 全部为静态方法：

| 方法 | 说明 |
|---|---|
| `getConfig('key', $default)` | 读取 `sn-support` 配置（dot notation） |
| `getModel('name', $shouldException)` | 获取配置中的模型类名，第二个参数 `false` 时不抛异常 |
| `getTenantModel()` | 获取租户模型类名 |
| `isTenancyEnabled()` | 判断多租户是否启用 |
| `getFilesystemDisk()` | 获取文件系统磁盘（回退到 Filament 默认盘） |
| `getScopeFromConfig('sn-cms.scopeable')` | 从配置创建 ScopeableContext |

### 关键辅助函数

| 函数 | 说明 |
|---|---|
| `get_sn($id, $type)` | 生成唯一编号 |
| `sn_route($name, $params)` | 租户感知路由，多租户启用时自动添加 tenant 参数 |
| `files_url($files, $disk)` | 解析文件 URL |
| `href_format($url, $newTab, $spaMode)` | 生成带 wire:navigate 的链接 |
| `through_cache($key, $callback)` | 缓存穿透模式 |
| `filter_richeditor($content)` | 去除富文本中包裹图片的 anchor 标签 |
| `tree_to_flatten($tree)` | 递归将树结构扁平化 |
| `scopeable_context($input)` | 创建 ScopeableContext |
| `scopeable_query($query, $scope)` | 对查询应用 scope 过滤 |
| `exception_log($exception, $name, $message)` | 结构化异常日志 |

### 正确命名空间速查

| 类别 | 命名空间 |
|---|---|
| FormComponents 工厂 | `Wsmallnews\Support\Filament\Forms\FormComponents` |
| FilterComponents 工厂 | `Wsmallnews\Support\Filament\Filters\FilterComponents` |
| ActionComponents 工厂 | `Wsmallnews\Support\Filament\Actions\ActionComponents` |
| 自定义表单字段 | `Wsmallnews\Support\Filament\Forms\Fields\` |
| Activity Logs 资源 | `Wsmallnews\Support\Filament\Resources\ActivityLogs\` |
| Tags 资源 | `Wsmallnews\Support\Filament\Resources\Tags\` |
| Livewire Base | `Wsmallnews\Support\Livewire\Base` |
| Livewire Traits | `Wsmallnews\Support\Livewire\Concerns\` |
| Model Traits | `Wsmallnews\Support\Models\Concerns\` |
| Models | `Wsmallnews\Support\Models\` |
| Casts | `Wsmallnews\Support\Casts\` |
| Enums | `Wsmallnews\Support\Enums\` |
| Data 对象 | `Wsmallnews\Support\Data\` |
| Contracts | `Wsmallnews\Support\Contracts\` |
| Utils | `Wsmallnews\Support\Support\Utils` |
| Facade | `Wsmallnews\Support\Facades\Support` |
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
