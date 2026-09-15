<?php

namespace Wsmallnews\Support\Features\Composition;

use Illuminate\Support\Arr;
use Wsmallnews\Support\Facades\CompositionRegistry;

/**
 * 内容编排渲染器：把 Composition 的行式布局数据解析为可渲染的组件清单。
 *
 * 编排数据（components json）：[{layout, left: [{type,label,description,extras}], right: [...]}]
 * 解析输出：[{layout, left: [{component_name, extras}], right: [...]}]
 *
 * 组件类型经 CompositionRegistry 按 $module（模块标识，插件 id）解析——与编排数据的
 * scope（归属隔离）正交，调用方显式传入。
 */
class CompositionRenderer
{
    /**
     * 行布局（lg+ 三等分栅格；lg 以下固定单列，按 left → right 顺序堆叠）
     */
    public const LAYOUT_FULL = 'full';          // 通栏

    public const LAYOUT_LEFT_NARROW = '1-2';    // 左1 右2

    public const LAYOUT_LEFT_WIDE = '2-1';      // 左2 右1

    public const LAYOUTS = [
        self::LAYOUT_FULL,
        self::LAYOUT_LEFT_NARROW,
        self::LAYOUT_LEFT_WIDE,
    ];

    /**
     * 解析编排行数据
     *
     * @param  array|null  $rows  Composition.components
     * @param  string  $module  模块标识（插件 id），CompositionRegistry 注册 key
     * @return array 可渲染行结构
     */
    public static function resolveRows(?array $rows, string $module): array
    {
        $resolved = [];

        foreach ($rows ?? [] as $row) {
            // 未知布局回退通栏，保证数据异常时仍可渲染
            $layout = in_array($row['layout'] ?? null, self::LAYOUTS, true) ? $row['layout'] : self::LAYOUT_FULL;

            $resolved[] = [
                'layout' => $layout,
                'left' => self::resolveSlot($row['left'] ?? [], $module),
                'right' => self::resolveSlot($row['right'] ?? [], $module),
            ];
        }

        return $resolved;
    }

    /**
     * 解析单个槽（left/right）内的组件清单；未注册的组件类型整项跳过
     */
    protected static function resolveSlot(array $items, string $module): array
    {
        $resolved = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? null;
            if (! $type) {
                continue;
            }

            $typeInfo = CompositionRegistry::getType($module, $type);
            if (! $typeInfo) {
                continue;
            }

            $resolved = array_merge($resolved, self::resolveComponents($typeInfo, $item));
        }

        return $resolved;
    }

    /**
     * 解析注册类型对应的 Livewire 组件（一个注册类型可映射多个组件），
     * 合并注册固定参数与编排 extras，并透传标题/描述（componentInfo）
     */
    protected static function resolveComponents(array $typeInfo, array $item): array
    {
        $currentComponents = Arr::wrap($typeInfo['components'] ?? $typeInfo['component'] ?? []);

        $extras = $item['extras'] ?? [];
        $extras['componentInfo'] = [
            'type' => $item['type'],
            'label' => $item['label'] ?? null,
            'description' => $item['description'] ?? null,
        ];

        $mapped = Arr::map($currentComponents, function ($currentComponent, $key) use ($extras) {
            if (is_scalar($currentComponent)) {
                return [
                    'component_name' => $currentComponent,
                    'extras' => $extras,
                ];
            }

            return [
                'component_name' => $key,
                'extras' => array_merge($currentComponent, $extras),
            ];
        });

        return array_values($mapped);
    }
}
