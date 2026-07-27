<?php

use Filament\Facades\Filament;
use Filament\Panel;
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
     * 
     * @param Panel|string|null  $panel
     * @return ?string
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
