<?php

namespace Wsmallnews\Support\Livewire\Components;

use Illuminate\Support\Collection;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\HasProperties;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 前端全局搜索组件（sn-support-components-search）。
 *
 * 搜索哪些内容由各扩展包注册的来源决定（见 SearchRegistry），
 * 组件不感知引擎与来源，只负责输入防抖、分组渲染与命中高亮。
 *
 * 用法：<livewire:sn-support-components-search placeholder="搜索…" :limit="5" />
 */
class Search extends Base
{
    use HasProperties;

    public ?string $query = null;

    /**
     * 绑定的搜索模块（搜索名/插件 ID）；null 时搜索所有已启用模块
     */
    public ?string $searchKey = null;

    /**
     * 覆盖每个来源的返回条数（null 使用来源/全局配置）
     */
    public ?int $limit = null;

    public string $placeholder = '';

    public function mount(): void
    {
        $this->placeholder = $this->placeholder ?: __('sn-support::search.placeholder');
    }

    public function render()
    {
        return view('sn-support::livewire.components.search', [
            'groups' => SearchFacade::search((string) $this->query, $this->searchKey, $this->limit),
            'debounce' => SupportUtils::getSearchConfig('debounce', '300ms'),
        ]);
    }

    /**
     * 高亮标题中的命中关键词（逐词、大小写不敏感，文本已转义）。
     */
    public function highlight(?string $text): string
    {
        $text = e((string) $text);

        $query = trim((string) $this->query);

        if ($query === '' || $text === '') {
            return $text;
        }

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $text = preg_replace('/(' . preg_quote($term, '/') . ')/iu', '<mark class="sn-search-highlight">$1</mark>', $text);
        }

        return $text;
    }
}
