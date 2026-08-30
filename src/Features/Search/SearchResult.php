<?php

namespace Wsmallnews\Support\Features\Search;

final class SearchResult
{
    /**
     * @param  string  $key  来源标识
     * @param  string  $group  分组标签
     * @param  string  $title  标题（已转义由视图处理，此处为纯文本）
     * @param  string  $description  描述
     * @param  string  $coverUrl  封面图 URL
     * @param  string  $url  跳转链接（由注册方 url 闭包提供，为空时渲染为无链接项）
     * @param  string  $badge  徽标文本
     * @param  string  $morphType  记录的 morph 别名
     */
    public function __construct(
        public readonly string $key,
        public readonly string $group,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $coverUrl = null,
        public readonly ?string $url = null,
        public readonly ?string $badge = null,
        public readonly ?string $morphType = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'group' => $this->group,
            'title' => $this->title,
            'description' => $this->description,
            'cover_url' => $this->coverUrl,
            'url' => $this->url,
            'badge' => $this->badge,
            'morph_type' => $this->morphType,
        ];
    }
}
