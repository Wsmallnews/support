<?php

namespace Wsmallnews\Support\Features\Composition;

use Closure;
use Illuminate\Support\Collection;

/**
 * 内容组件注册表：各扩展包在 packageBooted() 中向自己的模块注册可编排的内容组件，
 * 后台「内容编排」表单与前台渲染器经本注册表解析组件类型。
 *
 * key = 模块标识（插件 id，如 sn-cms），与数据 scope 正交——scope 管编排数据隔离
 * （scope_type/scope_id），module 管组件由哪个模块提供；派生 scope（如 sn-cms-footer）
 * 不参与注册表寻址。支持多模块实例（模块名 = 插件 ID，互不污染）。
 *
 * typeInfo 可选的上下文元数据（CompositionRenderer 构建期注入，行内左→右流动、跨行隔离）：
 * - provides => fn (array $extras): array —— 上下文提供者：从自身 extras 计算产物并入行上下文袋
 *   （如 post-detail 提供 ['post' => PostModel]），供同行后续组件消费；
 * - context  => ['post', ...] —— 上下文消费者：声明的键在自身 extras 未显式配置时从袋子注入
 *   （extras 显式配置优先）。页面级种子经 resolveRows(..., $pageContext) 传入，每行可用。
 */
class CompositionRegistry
{
    /**
     * 已注册的内容组件：[module => Collection<type, typeInfo>]
     *
     * @var Collection<string, Collection<string, array>>
     */
    protected Collection $modules;

    /**
     * 已注册的编排用途槽位：[module => Collection<purpose, meta>]
     *
     * meta 结构（经 normalizePurposeMeta 归一化）：
     * - label：槽位显示标签（string|Closure，消费时求值），后台 purpose 下拉用
     * - positions：[位置值 => 标签(string|Closure)]，标准位置（left/right/top/bottom）自动生成
     *   support 翻译键闭包，自定义位置注册时以 '值' => '标签|翻译键|闭包' 提供
     * - default：默认位置（未声明时取 positions 首个）
     * - layout_mode：?string 显式布局模式（未声明按位置语义推导，见 getLayoutMode）
     * - context：?Closure(array $params, array $scopeable): array<string, mixed> —— pageContext 提供者，
     *   把调用方路由参数（如 ['slug' => ...]）映射为页面级上下文（供构建期上下文注入）
     *
     * @var Collection<string, Collection<string, array>>
     */
    protected Collection $purposes;

    public function __construct()
    {
        $this->modules = collect();
        $this->purposes = collect();
    }

    /**
     * 注册内容组件类型
     *
     * @param  string  $module  模块标识（插件 id）
     * @param  array  $typeInfo  内容类型信息数组
     */
    public function register(string $module, array $typeInfo): static
    {
        $types = $this->getTypes($module);
        $type = $typeInfo['type'];

        $this->modules->put($module, $types->put($type, $typeInfo));

        return $this;
    }

    /**
     * 注册多个内容组件类型
     *
     * @param  string  $module  模块标识（插件 id）
     * @param  array  $typeInfos  内容类型信息数组，每个元素为一个内容类型信息数组
     */
    public function registers(string $module, array $typeInfos): static
    {
        foreach ($typeInfos as $typeInfo) {
            $this->register($module, $typeInfo);
        }

        return $this;
    }

    /**
     * 获取所有模块
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    /**
     * 获取指定模块的所有内容组件类型
     *
     * @return Collection 内容类型信息集合
     */
    public function getTypes(string $module): Collection
    {
        return $this->modules->get($module, collect());
    }

    /**
     * 获取指定模块的指定内容组件类型
     *
     * @return array|null 内容类型信息数组，如果不存在则返回 null
     */
    public function getType(string $module, string $type): ?array
    {
        return $this->getTypes($module)->firstWhere('type', $type);

    }

    /**
     * 获取指定模块的内容组件类型选项，用于下拉选择
     *
     * @return array 内容类型选项数组，键为类型标识，值为类型标签
     */
    public function getTypesOptions(string $module): array
    {
        return $this->getTypes($module)->mapWithKeys(function ($typeInfo) {
            return [$typeInfo['type'] => $typeInfo['label']];
        })->toArray();
    }

    /**
     * 注册模块的编排用途槽位，重复注册：数组形态整体替换、字符串形态仅覆盖标签（保留既有 meta）
     *
     * label / 自定义位置标签接受 string | Closure：闭包（fn () => __('...')）在消费时求值，
     * 推荐写法——与全库「翻译键留在注册处、调用时 __()」的习惯一致且无 boot 顺序竞态。
     * layout_mode：'stack'（单列堆叠，侧栏类槽位）| 'rows'（行式分栏）——未声明时按位置语义推导
     * （左/右 = stack，上/下 = rows），自定义位置建议显式声明。
     *
     * @param  string  $module  模块标识（插件 id）
     * @param  array<string, string|array{label: string|Closure, positions?: array, default?: string, layout_mode?: string, context?: Closure}>  $purposes
     */
    public function registerPurposes(string $module, array $purposes): static
    {
        $metas = $this->getPurposesMeta($module);

        foreach ($purposes as $purpose => $meta) {
            if (is_string($meta) && ($existing = $metas->get($purpose))) {
                $metas->put($purpose, [...$existing, 'label' => $meta]);

                continue;
            }

            $metas->put($purpose, $this->normalizePurposeMeta(is_string($meta) ? ['label' => $meta] : $meta));
        }

        $this->purposes->put($module, $metas);

        return $this;
    }

    /**
     * 获取指定模块的用途槽位选项（值 => 已解析标签），供后台编排表单 purpose 下拉
     *
     * @return Collection<string, string>
     */
    public function getPurposes(string $module): Collection
    {
        return $this->getPurposesMeta($module)->map(fn (array $meta) => $this->resolveLabel($meta['label']));
    }

    /**
     * 获取指定模块的指定用途槽位元数据（label/positions 标签均已求值为字符串）
     *
     * @return array|null{label: string, positions: array<string, string>, default: ?string, layout_mode: ?string, context: ?Closure} 槽位 meta，未注册返回 null
     */
    public function getPurpose(string $module, string $purpose): ?array
    {
        $meta = $this->getPurposesMeta($module)->get($purpose);

        if (! $meta) {
            return null;
        }

        return [...$meta,
            'label' => $this->resolveLabel($meta['label']),
            'positions' => collect($meta['positions'])->map(fn ($spec) => $this->resolveLabel($spec))->all(),
        ];
    }

    /**
     * 槽位的编辑布局模式：meta 显式声明的 layout_mode 优先；未声明时按位置语义推导
     * （左/右 = 窄栏堆叠，上/下 = 全宽行式）。无槽位（通用编排）不渲染位置字段，恒为行式；
     * position 为空时回退槽位默认位置（单位置槽位隐藏位置字段）
     */
    public function getLayoutMode(string $module, ?string $purpose, ?string $position): string
    {
        $meta = filled($purpose) ? $this->getPurpose($module, $purpose) : null;

        if (filled($meta['layout_mode'] ?? null) && in_array($meta['layout_mode'], [CompositionRenderer::LAYOUT_MODE_STACK, CompositionRenderer::LAYOUT_MODE_ROWS], true)) {
            return $meta['layout_mode'];
        }

        if (blank($meta)) {
            return CompositionRenderer::LAYOUT_MODE_ROWS;
        }

        $position ??= $meta['default'];

        return in_array($position, [CompositionRenderer::POSITION_LEFT, CompositionRenderer::POSITION_RIGHT], true)
            ? CompositionRenderer::LAYOUT_MODE_STACK
            : CompositionRenderer::LAYOUT_MODE_ROWS;
    }

    protected function getPurposesMeta(string $module): Collection
    {
        return $this->purposes->get($module, collect());
    }

    /**
     * 求值标签：闭包在消费时调用（规避 provider boot 顺序导致的跨包翻译竞态），
     * 字符串过 __()（翻译键或纯文本均可）
     */
    protected function resolveLabel(string | Closure $spec): string
    {
        return $spec instanceof Closure ? (string) app()->call($spec) : __($spec);
    }

    /**
     * 归一化槽位 meta：positions 支持 ['left', 'right']（标准位置，label 自动生成为 support
     * 翻译键闭包，注册方无需接触 support 的键）与 ['left', 'spotlight' => '标签|翻译键|闭包']
     * 混合形态；default 未声明取首个。标签统一存 string|Closure，消费时经 resolveLabel 求值。
     *
     * @param  array{label: string|Closure, positions?: array, default?: string, layout_mode?: string, context?: Closure}  $meta
     * @return array{label: string|Closure, positions: array<string, string|Closure>, default: ?string, layout_mode: ?string, context: ?Closure}
     */
    protected function normalizePurposeMeta(array $meta): array
    {
        $positions = collect($meta['positions'] ?? [
            CompositionRenderer::POSITION_LEFT,
            CompositionRenderer::POSITION_RIGHT,
        ])
            ->mapWithKeys(function ($label, $key) {
                $value = is_int($key) ? $label : $key;

                return [$value => is_int($key) ? $this->standardPositionLabel($value) : $label];
            })
            ->all();

        return [
            'label' => $meta['label'],
            'positions' => $positions,
            'default' => $meta['default'] ?? array_key_first($positions),
            'layout_mode' => $meta['layout_mode'] ?? null,
            'context' => $meta['context'] ?? null,
        ];
    }

    /**
     * 标准位置标签（闭包形态，消费时按当前 locale 翻译；非标准值回退原值）
     */
    protected function standardPositionLabel(string $value): Closure
    {
        return in_array($value, CompositionRenderer::POSITIONS, true)
            ? fn (): string => __("sn-support::composition.position.{$value}")
            : fn (): string => $value;
    }

    /**
     * 检查指定模块的指定内容组件类型是否有表单配置
     *
     * @param  string  $module  模块标识（插件 id）
     * @param  string  $type  内容类型标识
     * @param  array  $arguments  表单参数，当表单配置为闭包时使用
     */
    public function hasTypeForms(string $module, string $type, array $arguments = []): bool
    {
        $forms = $this->getTypeForms($module, $type, $arguments);

        return $forms && count($forms) > 0;
    }

    /**
     * 获取指定模块的指定内容组件类型的表单配置
     *
     * @param  string  $module  模块标识（插件 id）
     * @param  string  $type  内容类型标识
     * @param  array  $arguments  表单参数，当表单配置为闭包时使用
     * @return array 表单配置数组
     */
    public function getTypeForms(string $module, string $type, array $arguments = []): array
    {
        $typeInfo = $this->getType($module, $type);

        $forms = $typeInfo['forms'] ?? [];

        return $forms instanceof Closure ? app()->call($forms, $arguments) : $forms;
    }
}
