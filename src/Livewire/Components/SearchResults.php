<?php

namespace Wsmallnews\Support\Livewire\Components;

use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Livewire\Base;

/**
 * 搜索结果页核心组件（sn-support::components.search-results）。
 *
 * 供各扩展包的结果页使用（页面布局/路由由调用方定义，本组件只负责结果区）：
 * 关键词与地址栏 ?q= 双向同步。
 *
 * 搜索触发方式由 show_search_button 决定（组件属性 > 模块声明 > 全局）：
 * - 显示按钮：输入框为 deferred 绑定（wire:model），按钮/回车调用 search() 显式触发
 *   （Livewire 发请求时合并 deferred 待定值，更新先于 action 应用）
 * - 无按钮：wire:model.live.debounce 输入防抖自动搜索，回车立即刷新
 *
 * 用法：<livewire:sn-support::components.search-results :limit="10" placeholder="搜索文章" />
 * 绑定模块：<livewire:sn-support::components.search-results module="sn-cms" />
 */
class SearchResults extends Base
{
    /**
     * 关键词，与地址栏 ?q= 双向同步
     */
    #[Url(as: 'q', except: '')]
    public ?string $query = null;

    /**
     * 绑定的搜索模块（搜索名/插件 ID）；null 时搜索所有已启用模块
     */
    public ?string $module = null;

    /**
     * 覆盖每个来源的返回条数（null 使用来源/全局配置）
     */
    public ?int $limit = null;

    /**
     * 是否渲染一体化搜索按钮：null 时回退模块声明 → 全局 sn-support.search.show_search_button
     */
    public ?bool $showButton = null;

    public string $placeholder = '';

    public function mount(): void
    {
        $this->placeholder = $this->placeholder ?: __('sn-support::search.placeholder');
    }

    public function render()
    {
        return view('sn-support::livewire.components.search-results', [
            'groups' => SearchFacade::search($this->module, (string) $this->query, $this->limit),
            'debounce' => (string) SearchFacade::resolveConfig($this->module, 'debounce', '300ms'),
            'itemRenderers' => SearchFacade::itemRenderers($this->module),
            'showSearchButton' => $this->isShowButton(),
        ]);
    }

    /**
     * 是否渲染一体化搜索按钮：组件属性 > 模块声明（Search::config）> 全局配置，默认不显示
     * （结果页本身即 page 形态，不受 display 配置门控）
     */
    public function isShowButton(): bool
    {
        if ($this->showButton !== null) {
            return $this->showButton;
        }

        return (bool) SearchFacade::resolveConfig($this->module, 'show_search_button', false);
    }

    /**
     * 显式搜索（按钮 / 回车触发）：deferred 绑定的 query 待定值由 Livewire 合并进本请求
     * （updates 先于 actions 应用），组件随后以最新关键词重新渲染结果
     */
    public function search(): void {}

    /**
     * 一体化搜索按钮：自定义 HTML（HtmlString）传入 wrapper 的 suffix 渲染，点击调用 search()
     */
    public function getSearchButtonHtml(): HtmlString
    {
        return new HtmlString(view('sn-support::livewire.components.search-button', [
            'type' => 'button',
            'wireClick' => 'search',
        ])->render());
    }
}
