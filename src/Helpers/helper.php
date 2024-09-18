<?php

if (!function_exists('client_unique')) {
    /**
     * 获取客户端唯一标识
     *
     * @return boolean
     */
    function client_unique()
    {
        // $httpName = app('http')->getName();
        $httpName = '';
        $url = request()->baseUrl();
        $ip = request()->ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $key = $httpName . ':' . $url . ':' . $ip . ':' . $user_agent;

        return md5($key);
    }
}



if (!function_exists('db_listen')) {
    /**
     * 获取客户端唯一标识
     *
     * @return boolean
     */
    function db_listen()
    {
        \Illuminate\Support\Facades\DB::listen(function ($query) {
            echo $query->sql . '<br>';
            echo $query->bindings . '<br>';
            echo $query->time . '<br>';
        });
    }
}