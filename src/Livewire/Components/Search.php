<?php

namespace Wsmallnews\Support\Livewire\Components;

use Filament\Support\Facades\FilamentView;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\HasProperties;

/**
 * 前端全局搜索组件（sn-support::components.search）。
 *
 * 搜索哪些内容由各扩展包注册的来源决定（见 SearchRegistry），
 * 组件不感知引擎与来源，只负责输入防抖、分组渲染与命中高亮。
 *
 * 展示方式由 display 决定（优先级：组件属性 > 模块声明 Search::config > sn-support.search.display）：
 * - dropdown：输入即搜，结果浮层展示在搜索框下方
 * - page：回车跳转到独立搜索结果页。地址经 Search::config($search, ['page' => ...])
 *   按模块声明（URL 字符串由 support 拼接 ?q=关键词；闭包接收关键词并自行返回完整 URL），
 *   未声明的模块走全局兜底 sn-support.search.page
 *
 * page 模式可经 show_search_button 配置（组件属性 showButton > 模块声明 > 全局配置）
 * 在搜索框右端渲染一体化搜索按钮，点击与回车等价，替代 ↵ Enter 提示。
 *
 * 用法：<livewire:sn-support::components.search placeholder="搜索…" :limit="5" />
 * 页面模式：<livewire:sn-support::components.search display="page" module="sn-cms" />
 */
class Search extends Base
{
    use HasProperties;

    public ?string $query = null;

    /**
     * 绑定的模块（插件 ID）；null 时搜索所有已启用模块
     */
    public ?string $module = null;

    /**
     * 覆盖每个来源的返回条数（null 使用来源/全局配置）
     */
    public ?int $limit = null;

    /**
     * 结果展示方式：null 时回退 sn-support.search.display 配置
     */
    public ?string $display = null;

    /**
     * 是否渲染一体化搜索按钮（仅 display = page 生效）：
     * null 时回退模块声明（Search::config）→ 全局 sn-support.search.show_search_button
     */
    public ?bool $showButton = null;

    public string $placeholder = '';

    public function mount(): void
    {
        $this->placeholder = $this->placeholder ?: __('sn-support::search.placeholder');

        // mount 时解析进属性本身（组件属性 > 模块声明 > 全局），
        // 视图直接读 $display——公共属性会遮蔽同名视图数据，不能只靠 render 传值
        $this->display = $this->display ?? (string) SearchFacade::resolveConfig($this->module, 'display', 'dropdown');
    }

    public function render()
    {
        $groups = $this->isDropdownDisplay()
            ? SearchFacade::search($this->module, (string) $this->query, $this->limit)
            : collect();

        return view('sn-support::livewire.components.search', [
            'groups' => $groups,
            'debounce' => (string) SearchFacade::resolveConfig($this->module, 'debounce', '300ms'),
            'itemRenderers' => SearchFacade::itemRenderers($this->module),
            'showSearchButton' => $this->isShowButton(),
        ]);
    }

    /**
     * 是否为下拉浮层展示方式
     */
    public function isDropdownDisplay(): bool
    {
        return $this->getDisplay() === 'dropdown';
    }

    /**
     * 展示方式解析：组件属性 > 模块声明（Search::config）> 全局 sn-support.search.display
     */
    public function getDisplay(): string
    {
        return $this->display
            ?? (string) SearchFacade::resolveConfig($this->module, 'display', 'dropdown');
    }

    /**
     * 是否渲染一体化搜索按钮：仅 page 模式生效；
     * 组件属性 > 模块声明（Search::config）> 全局配置，默认不显示
     */
    public function isShowButton(): bool
    {
        if ($this->getDisplay() !== 'page') {
            return false;
        }

        if ($this->showButton !== null) {
            return $this->showButton;
        }

        return (bool) SearchFacade::resolveConfig($this->module, 'show_search_button', false);
    }

    /**
     * 一体化搜索按钮：自定义 HTML（HtmlString）传入 wrapper 的 suffix 渲染，点击与回车等价
     */
    public function getSearchButtonHtml(): HtmlString
    {
        return new HtmlString(view('sn-support::livewire.components.search-button', [
            'type' => 'button',
            'wireClick' => 'gotoSearchPage',
        ])->render());
    }

    /**
     * page 模式：回车跳转到搜索结果页，关键词由 resolvePage 拼进地址（闭包声明自行拼接）
     */
    public function gotoSearchPage(): void
    {
        $term = trim((string) $this->query);

        if ($term === '') {
            return;
        }

        $url = SearchFacade::resolvePage($this->module, $term);

        if (blank($url)) {
            return;
        }

        $this->redirect($url, FilamentView::hasSpaMode());
    }
}
