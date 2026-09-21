<?php

namespace Wsmallnews\Support\Features\Modules;

/**
 * 模块描述符：一个已登记模块的身份信息。
 *
 * id 是全生态的寻址键——config root（config("{id}.xxx")）、各功能注册表
 * （SidebarMenuRegistry/UserConfig/Search/Feed 等）的 module 参数、
 * HasModuleContext 的 :module prop，全部使用同一 id。
 *
 * 后续需要新的模块级元信息（视图命名空间、启停状态等）在此加 readonly 属性，
 * register() 用命名参数构造，登记侧写法保持稳定。
 */
final class Module
{
    public function __construct(
        public readonly string $id,
        public readonly string $namespace,
        public readonly ?string $plugin = null,
    ) {}
}
