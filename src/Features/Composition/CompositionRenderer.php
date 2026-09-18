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
     * @param  array  $pageContext  页面级上下文种子（如详情页注入的当前文章），每行可用
     * @return array 可渲染行结构
     */
    public static function resolveRows(?array $rows, string $module, array $pageContext = []): array
    {
        $resolved = [];

        foreach ($rows ?? [] as $row) {
            // 未知布局回退通栏，保证数据异常时仍可渲染
            $layout = in_array($row['layout'] ?? null, self::LAYOUTS, true) ? $row['layout'] : self::LAYOUT_FULL;

            // 行上下文袋：以页面上下文为种子，槽内按序（左→右）流动，行末丢弃（跨行隔离）
            $context = $pageContext;

            $resolved[] = [
                'layout' => $layout,
                'left' => self::resolveSlot($row['left'] ?? [], $module, $context),
                'right' => self::resolveSlot($row['right'] ?? [], $module, $context),
            ];
        }

        return $resolved;
    }

    /**
     * 解析单个槽（left/right）内的组件清单；未注册的组件类型整项跳过。
     * 槽内按序处理上下文：先消费（声明 context 的条目从袋子注入缺失键，extras 显式配置优先），
     * 再解析组件，最后提供（声明 provides 的条目把计算结果并入袋子，供后续条目使用）
     *
     * @param  array  $context  行上下文袋（引用传递，左槽的提供物会流到右槽）
     */
    protected static function resolveSlot(array $items, string $module, array &$context): array
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

            // 消费：extras 已显式配置的键不被覆盖
            $extras = $item['extras'] ?? [];
            foreach ($typeInfo['context'] ?? [] as $contextKey) {
                if (! array_key_exists($contextKey, $extras) && array_key_exists($contextKey, $context)) {
                    $extras[$contextKey] = $context[$contextKey];
                }
            }

            $resolved = array_merge($resolved, self::resolveComponents($typeInfo, [...$item, 'extras' => $extras]));

            // 提供：闭包接收注入后的 extras，返回键值数组并入袋子（空数组安全）
            if (isset($typeInfo['provides'])) {
                $provided = app()->call($typeInfo['provides'], ['extras' => $extras]) ?? [];
                $context = array_merge($context, $provided);
            }
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
            'show_header' => (bool) ($item['show_header'] ?? true),
        ];

        // 外层容器开关：透传给组件的 contained 属性（use CanBeContained 的组件生效，未声明的组件忽略此参数）
        $extras['contained'] = (bool) ($item['contained'] ?? true);

        // 编排块标记：块内组件声明 embedded 属性后可据此让渡页面级职责（如 SEO 归路由页所有）
        $extras['embedded'] = true;

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
