<?php

namespace Wsmallnews\Support\Features\Search\Engines;

/**
 * 支持接收模块级搜索配置的引擎：注册表解析引擎后注入模块声明的选项
 * （Search::config($module, [...])），引擎读取时未声明的键回退全局
 * sn-support.search.* 配置。自定义引擎按需实现，不强制。
 */
interface ConfigurableEngine extends Engine
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function setSearchConfig(array $config): static;
}
