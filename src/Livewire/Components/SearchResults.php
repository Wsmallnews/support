<?php

namespace Wsmallnews\Support\Livewire\Components;

use Livewire\Attributes\Url;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 搜索结果页核心组件（sn-support::components.search-results）。
 *
 * 供各扩展包的结果页使用（页面布局/路由由调用方定义，本组件只负责结果区）：
 * 关键词与地址栏 ?q= 双向同步，输入防抖实时刷新，回车立即搜索并更新地址栏。
 *
 * 用法：<livewire:sn-support::components.search-results :limit="10" />
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

    public function render()
    {
        return view('sn-support::livewire.components.search-results', [
            'groups' => SearchFacade::search($this->module, (string) $this->query, $this->limit),
            'debounce' => SupportUtils::getSearchConfig('debounce', '300ms'),
            'itemRenderers' => SearchFacade::itemRenderers($this->module),
        ]);
    }
}
