<?php

namespace Wsmallnews\Support\Features\Search;

use Illuminate\Database\Eloquent\Model;

final class SearchResult
{
    /**
     * @param  string  $key  Source key
     * @param  string  $group  Group label
     * @param  string  $title  Plain title; the view escapes it
     * @param  string  $description  Description
     * @param  string  $coverUrl  Cover image URL
     * @param  string  $url  Link from the registrant's url closure; empty renders an unlinked item
     * @param  string  $badge  Badge text
     * @param  string  $morphType  Morph alias of the record
     * @param  Model|null  $record  Original model, reachable as ->record from custom views; may be absent for custom results
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
        public readonly ?Model $record = null,
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
