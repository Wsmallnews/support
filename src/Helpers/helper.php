<?php

use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

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
        \Illuminate\Support\Facades\DB::listen(function ($query) {
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
     * @return \Wsmallnews\Support\Features\Currency
     */
    function sn_currency()
    {
        return app(\Wsmallnews\Support\Features\Currency::class);
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
