<?php

namespace Wsmallnews\Support\Http\Controllers;

use Illuminate\Http\Response;
use Wsmallnews\Support\Facades\Feed;

/**
 * 站点级 RSS 端点：/feed（整站聚合流）与 /feed/{name}（具名流）由 support 提供；
 * 各模块另在自己的路由前缀内注册模块端点（如 /cms/feed、/cms/feed/posts，仅输出
 * 本模块流，路径前缀部署下多模块共用域名时的内容隔离），归属经路由
 * ->defaults('feed_module', '<插件 ID>') 声明。
 */
class FeedController
{
    /**
     * 整站聚合流：合并全部可见模块流，按时间倒序（结果经 FeedRegistry 按流缓存）。
     */
    public function index(): Response
    {
        return $this->response(Feed::render()->toHtml());
    }

    /**
     * 具名流：单个模块内容流（流不存在或域名不匹配时 404）。
     */
    public function show(string $name): Response
    {
        abort_if(Feed::getFeed($name) === null, 404);

        return $this->response(Feed::render($name)->toHtml());
    }

    /**
     * 模块聚合流：仅合并该模块的可见流（模块端点，如 /cms/feed）。
     *
     * feed_module 不走方法参数注入：Laravel 传路由参数按位置（array_values），
     * defaults 注入键与 URI 段的先后顺序无法保证对齐，故显式从路由参数取。
     */
    public function moduleIndex(): Response
    {
        $module = (string) request()->route()->parameter('feed_module');

        return $this->response(Feed::renderModule($module)->toHtml());
    }

    /**
     * 模块具名流：输出属于该模块的具名流（不属于该模块或不可见时 404，如 /cms/feed/posts）。
     */
    public function moduleShow(string $name): Response
    {
        $module = (string) request()->route()->parameter('feed_module');

        abort_if(Feed::getFeed($name, $module) === null, 404);

        return $this->response(Feed::renderModule($module, $name)->toHtml());
    }

    protected function response(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
