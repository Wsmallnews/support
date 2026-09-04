<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Data\ScopeableContext;
use Wsmallnews\Support\Features\Currency;
use Wsmallnews\Support\Helpers\ScopeableHelper;
use Wsmallnews\Support\Support\Utils;

if (! function_exists('get_sn')) {
    /**
     * 获取唯一编号
     *
     * @param  mixed  $id  唯一标识
     * @param  string  $type  类型
     * @return string
     */
    function get_sn($id, $type = '')
    {
        $id = (string) $id;

        $rand = $id < 9999 ? mt_rand(100000, 99999999) : mt_rand(100, 99999);
        $sn = date('Yhis') . $rand;

        $id = str_pad($id, (24 - strlen($sn)), '0', STR_PAD_BOTH);

        return $type . $sn . $id;
    }
}

if (! function_exists('client_unique')) {
    /**
     * 获取客户端唯一标识
     *
     * @return bool
     */
    function client_unique()
    {
        $httpName = '';
        $url = request()->path();
        $ip = request()->ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $key = $httpName . ':' . $url . ':' . $ip . ':' . $user_agent;

        return md5($key);
    }
}

if (! function_exists('db_listen')) {
    /**
     * 获取客户端唯一标识
     *
     * @return bool
     */
    function db_listen()
    {
        DB::listen(function ($query) {
            $sql = $query->sql . ' ## | ';
            foreach ($query->bindings as $k => $v) {
                $sql .= $k . ' => ' . $v . ' | ';
            }
            $sql .= ' ## ' . $query->time . '<br>';

            echo $sql;
        });
    }
}

if (! function_exists('sn_currency')) {
    /**
     * 获取自定义 currency 操作类
     *
     * @return Currency
     */
    function sn_currency()
    {
        return app(Currency::class);
    }
}

if (! function_exists('exception_log')) {
    /**
     * 格式化exception 记录日志，重要地方使用
     *
     * @param  object  $exception
     * @param  string  $name
     * @param  string  $message
     * @return void
     */
    function exception_log($exception, $name = '', $message = '')
    {
        $logInfo = [
            "========== $name EXCEPTION LOG INFO BEGIN ==========",
            '[ Message ] ' . var_export('[' . $exception->getCode() . ']' . $exception->getMessage() . ' ' . $message, true),
            '[ File ] ' . var_export($exception->getFile() . ':' . $exception->getLine(), true),
            '[ Trace ] ' . var_export($exception->getTraceAsString(), true),
            "========== $name EXCEPTION LOG INFO ENDED ==========",
        ];

        $logInfo = implode(PHP_EOL, $logInfo) . PHP_EOL;
        Log::error($logInfo);
    }
}

if (! function_exists('through_cache')) {
    function through_cache($key, $callback, $store = null, $is_force = false, $ttl = 0)
    {
        $cache = Cache::store($store);

        if (! $is_force && $cache->has($key)) {
            // 直接取缓存
            return $cache->get($key);
        }

        $data = $callback();

        $cache->put($key, $data, $ttl);

        return $data;
    }
}

if (! function_exists('href_format')) {

    /**
     * href 跳转地址格式化
     */
    function href_format(?string $url, bool $shouldOpenInNewTab = false, ?bool $shouldOpenInSpaMode = null): Htmlable
    {
        if (blank($url)) {
            return new HtmlString('');
        }

        $html = "href=\"{$url}\"";

        if ($shouldOpenInNewTab) {
            $html .= ' target="_blank"';
        } elseif ($shouldOpenInSpaMode ?? (FilamentView::hasSpaMode($url))) {
            $html .= ' wire:navigate';
        }

        return new HtmlString($html);
    }
}

if (! function_exists('files_url')) {
    function files_url($originalFiles, $disk = null)
    {
        $disk = $disk ?? Utils::getFilesystemDisk();

        $files = Arr::wrap($originalFiles);

        $diskUrl = config('filesystems.disks.' . $disk . '.url');
        $files = Arr::map($files, function ($file) use ($diskUrl) {
            return str($file)->startsWith(['http://', 'https://', 'data:image/']) ? $file : $diskUrl . '/' . $file;
        });

        return Arr::accessible($originalFiles) ? $files : Arr::first($files);
    }
}

if (! function_exists('filter_richeditor')) {
    function filter_richeditor($content)
    {
        $regex = '/<a[^>]*>(<img[^>]*>).*?<figcaption[^>]*>.*?<\/figcaption>.*?<\/a>/is';

        return preg_replace($regex, '$1', $content);
    }
}

if (! function_exists('frontend_has_tenancy')) {
    /**
     * 前端是否有租户
     *
     * @return bool
     */
    function frontend_has_tenancy()
    {
        return request()->attributes->get('has_tenancy', false);
    }
}

if (! function_exists('frontend_current_tenant')) {
    /**
     * 前端当前租户
     *
     * @return Model
     */
    function frontend_current_tenant()
    {
        return request()->attributes->get('current_tenant', null);
    }
}

if (! function_exists('has_tenancy')) {
    /**
     * 全局是否有租户（包括用户端租户信息）
     */
    function has_tenancy(): bool
    {
        return is_null(current_tenant()) ? false : true;
    }
}

if (! function_exists('current_tenant')) {
    /**
     * 全局当前租户（包括用户端租户信息）
     */
    function current_tenant(): ?Model
    {
        $teannt = null;
        if (Filament::getCurrentPanel()) {
            // 当前在后台面板
            $teannt = Filament::getTenant();
        } else {
            // 用户端
            if (frontend_has_tenancy()) {
                $teannt = frontend_current_tenant();
            }
        }

        return $teannt;
    }
}

if (! function_exists('get_tenancy_scope_name')) {
    /**
     * 获取租户作用域名称
     */
    function get_tenancy_scope_name(Panel | string | null $panel = null): ?string
    {
        $panel = $panel instanceof Panel ? $panel : (is_string($panel) ? Filament::getPanel($panel) : Filament::getCurrentPanel());

        return $panel?->getTenancyScopeName();
    }
}

if (! function_exists('is_in_panel')) {
    /**
     * 全局是否有面板（包括用户端租户信息）
     */
    function is_in_panel(): bool
    {
        return ! is_null(Filament::getCurrentPanel());
    }
}

if (! function_exists('tree_to_flatten')) {
    /**
     * 递归将树结构转换为平面数组
     *
     * @param  Collection  $tree
     * @return Collection
     */
    function tree_to_flatten($tree)
    {
        return $tree->flatMap(function ($node) {
            // 递归处理子节点，并将当前节点与子节点数组合并
            $children = $node->relationLoaded('children') ? tree_to_flatten($node->children) : collect();

            // 将当前节点和所有子节点合并成一个新集合
            return collect([$node])->merge($children);
        });
    }
}

if (! function_exists('sn_route')) {
    /**
     * 多租户路由处理
     *
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     */
    function sn_route($name, $parameters = [], $absolute = true): string
    {
        if (has_tenancy()) {
            $parameters = Arr::wrap($parameters);

            if (! isset($parameters['tenant'])) {        // 没有租户参数,则添加租户参数
                $tenant = current_tenant();
                $parameters['tenant'] = $tenant;        // 租户参数
            }
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('remove_query_param_from_url')) {
    /**
     * 移除 url 地址 query 参数
     */
    function remove_query_param_from_url(string $url, string | array $keys): string
    {
        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $query);

        $keys = Arr::wrap($keys);
        foreach ($keys as $key) {
            if (isset($query[$key])) {
                unset($query[$key]);
            }
        }

        $newQuery = http_build_query($query);

        return $parts['scheme'] . '://' . $parts['host']
            . (($parts['port'] ?? '') ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '')
            . ($newQuery ? '?' . $newQuery : '');
    }
}

if (! function_exists('scopeable_context')) {
    /**
     * Create a ScopeableContext instance from various inputs.
     *
     * @param  mixed  $input  Array, ScopeableContext, or config key
     */
    function scopeable_context(mixed $input): ScopeableContext
    {
        return ScopeableHelper::resolve($input);
    }
}

if (! function_exists('scopeable_query')) {
    /**
     * Apply scope to a query builder.
     *
     * @param  Builder  $query
     * @param  mixed  $scope  Array, ScopeableContext, or config key
     * @return Builder
     */
    function scopeable_query($query, mixed $scope)
    {
        return ScopeableHelper::applyToQuery($query, $scope);
    }
}

if (! function_exists('text_highlight')) {
    /**
     * 文本关键词高亮：逐词、大小写不敏感，文本已转义，命中处包 <mark> 标签。
     * 通用助手（不限于搜索场景），搜索结果条目视图（自定义视图与默认模板）亦直接调用。
     */
    function text_highlight(?string $text, ?string $query): string
    {
        $text = e((string) $text);

        $query = trim((string) $query);

        if ($query === '' || $text === '') {
            return $text;
        }

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $text = preg_replace('/(' . preg_quote($term, '/') . ')/iu', '<mark class="sn-text-highlight">$1</mark>', $text);
        }

        return $text;
    }
}

if (! function_exists('sn_badge_color')) {
    /**
     * 前端 sn-badge 徽章颜色解析：把 Filament 语义颜色（预置色名字符串 / Color::Blue 色板数组 / hex 字符串）
     * 解析为 sn-badge 可用的 class 与 style。
     *
     * - $variant：soft（默认浅底）/ outline（描边）/ solid（实底醒目，可用于徽章与醒目 tab 选中态）
     * - 色名在 sn-badge 六色清单内（primary/danger/success/info/warning/gray）→ 返回对应变体的预置类
     * - 其余色名（经 Filament 色板注册表解析）或色板数组 → 返回动态类 + --sn-color-* 变量 style（暗色变量一并输出）；
     *   solid 动态路径按 WCAG 4.5:1 对比度挑选文字色（复刻 Filament ButtonComponent 的结论方向）
     * - 解析失败 → 回退 gray 预置类
     *
     * @param  string  $variant  soft | outline | solid
     * @param  bool  $asVariables  为 true 时跳过静态类路径，强制输出 --sn-color-* 变量 style
     *                             （用于无法挂静态类的场景，如 fi-tabs 选中态注入；
     *                             色名会经 Filament 色板注册表解析，自定义主题色可能与 CSS 端存在偏差）
     * @return array{class: string, style: string}
     */
    function sn_badge_color(string | array | null $color, string $variant = 'soft', bool $asVariables = false): array
    {
        $named = ['primary', 'danger', 'success', 'info', 'warning', 'gray'];

        $namedClass = match ($variant) {
            'outline' => 'sn-badge-outline-{color}',
            'solid' => 'sn-badge-solid-{color}',
            default => 'sn-badge-{color}',
        };

        // 色名 → 预置变体类
        if (is_string($color) && in_array($color, $named) && ! $asVariables) {
            return ['class' => str_replace('{color}', $color, $namedClass), 'style' => ''];
        }

        // 其余输入统一解析为 Filament 色板（Color::Blue 数组原样；色名/hex 走 FilamentColor 注册表）
        $palette = $color;
        if (is_string($color) && filled($color)) {
            $palette = FilamentColor::getColor($color);
        }

        if (! is_array($palette) || blank($palette['500'] ?? null) || blank($palette['700'] ?? null)) {
            return ['class' => str_replace('{color}', 'gray', $namedClass), 'style' => ''];
        }

        // solid：实底取主题正色 500（不深于导航主题色，避免抢重点）+ 白字优先策略：
        // 对比度 ≥2:1 即用白字（品牌视觉权衡，徽章为短词加粗场景；完全无障碍场景请用 soft/outline），
        // 仅极浅色（yellow/lime 系，连 2:1 都不到）回退 950 深字
        if ($variant === 'solid') {
            $bg = $palette['500'] ?? $palette['600'];
            $lightText = $palette['50'] ?? '#ffffff';
            $darkText = $palette['950'] ?? $palette['900'];

            $text = (Color::calculateContrastRatio($bg, $lightText) >= 2.0)
                ? $lightText
                : $darkText;

            return [
                'class' => 'sn-badge-dynamic-solid',
                'style' => "--sn-color-bg: {$bg}; --sn-color-text: {$text}; --sn-color-dark-bg: {$bg}; --sn-color-dark-text: {$text};",
            ];
        }

        // soft / outline：主色透明度路线（变量注入 500/600/700 色值，CSS 端混合保证色相与预置类一致）
        $class = $variant === 'outline' ? 'sn-badge-dynamic-outline' : 'sn-badge-dynamic';
        $style = sprintf(
            '--sn-color-bg: %s; --sn-color-text: %s; --sn-color-ring: %s; --sn-color-dark-bg: %s; --sn-color-dark-text: %s;',
            $palette['500'],
            $palette['700'],
            $palette['600'] ?? $palette['500'],
            $palette['500'],
            $palette['400'] ?? $palette['500'],
        );

        return ['class' => $class, 'style' => $style];
    }
}
