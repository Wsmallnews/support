<?php

namespace Wsmallnews\Support\Livewire\Components;

use Filament\Support\Facades\FilamentView;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\HasProperties;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 前端全局搜索组件（sn-support::components.search）。
 *
 * 搜索哪些内容由各扩展包注册的来源决定（见 SearchRegistry），
 * 组件不感知引擎与来源，只负责输入防抖、分组渲染与命中高亮。
 *
 * 展示方式由 display 决定（优先级：调用方传入 > sn-support.search.display 默认配置）：
 * - dropdown：输入即搜，结果浮层展示在搜索框下方
 * - page：回车跳转到独立搜索结果页。地址经 Search::config($search, ['page' => ...])
 *   按模块声明（URL 字符串由 support 拼接 ?q=关键词；闭包接收关键词并自行返回完整 URL），
 *   未声明的模块走全局兜底 sn-support.search.page
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

    public string $placeholder = '';

    public function mount(): void
    {
        $this->placeholder = $this->placeholder ?: __('sn-support::search.placeholder');

        $this->display = $this->display ?: SupportUtils::getSearchConfig('display', 'dropdown');
    }

    public function render()
    {
        $groups = $this->isDropdownDisplay()
            ? SearchFacade::search($this->module, (string) $this->query, $this->limit)
            : collect();

        return view('sn-support::livewire.components.search', [
            'groups' => $groups,
            'debounce' => SupportUtils::getSearchConfig('debounce', '300ms'),
            'itemRenderers' => SearchFacade::itemRenderers($this->module),
        ]);
    }

    /**
     * 是否为下拉浮层展示方式
     */
    public function isDropdownDisplay(): bool
    {
        return $this->getDisplay() === 'dropdown';
    }

    public function getDisplay(): string
    {
        return $this->display ?: SupportUtils::getSearchConfig('display', 'dropdown');
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
