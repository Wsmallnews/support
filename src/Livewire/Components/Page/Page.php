<?php

namespace Wsmallnews\Support\Livewire\Components\Page;

use Wsmallnews\Support\Facades\Seo;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\Scopeable;
use Wsmallnews\Support\Models\Page as PageModel;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 站点页面内容组件：宿主路由页（如 cms 的 /cms/pages/{slug}）与首页引入，承载页面解析与渲染。
 *
 * 寻址双通道：传 slug（路由页，查询 + 404 + SEO）或直接注入 page 实例（首页等已解析场景，跳过查询）；
 * withSeo = false 时让渡 SEO（首页由宿主声明站点级 SEO）。
 *
 * 内容双通道互斥（编排优先）：绑定编排渲染编排行；未绑定时渲染页面自有 content；均无渲染空态。
 */
class Page extends Base
{
    use Scopeable;

    public ?string $slug = null;

    /**
     * 页面实例注入（优先于 slug 查询；宿主已保证 published）。
     * 注意命名避开 page：Livewire 组件属性会暴露给视图，与渲染数据 page 撞名会互相覆盖
     */
    public ?PageModel $pageRecord = null;

    public bool $withSeo = true;

    /**
     * 编排解析的组件来源模块（插件 id，宿主传入）；为空时编排通道恒为空，仅走 content 通道
     */
    public ?string $module = null;

    public function render()
    {
        $page = $this->pageRecord
            ?? SupportUtils::getPageModel()::query()
                ->published()
                ->snScope($this->scopeType, $this->scopeId)
                ->slug($this->slug ?? '')
                ->with('content')
                ->first();

        abort_if(! $page, 404);

        if ($this->withSeo) {
            Seo::title($page->title);
        }

        return view('sn-support::livewire.components.page', [
            'page' => $page,
            'rows' => filled($this->module) ? $page->resolveRows($this->module) : [],
        ]);
    }
}
