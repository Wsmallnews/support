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
 */
class CompositionRegistry
{
    /**
     * 已注册的内容组件：[module => Collection<type, typeInfo>]
     *
     * @var Collection<string, Collection<string, array>>
     */
    protected Collection $modules;

    public function __construct()
    {
        $this->modules = collect();
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
