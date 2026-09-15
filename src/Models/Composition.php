<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Contracts\HasSnSubject;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Models\Concerns\HasActivityLog;
use Wsmallnews\Support\Models\Concerns\HasOrderColumn;
use Wsmallnews\Support\Support\Utils;

class Composition extends SupportModel implements HasSnSubject
{
    use HasActivityLog;
    use HasOrderColumn;
    use SoftDeletes;

    protected $table = 'sn_compositions';

    protected $casts = [
        'components' => 'array',
        'options' => 'array',
        'status' => CompositionStatus::class,
    ];

    /**
     * 搜索字段（用于 morphFilter 关键词搜索）。
     */
    public static array $keywordSearchFields = ['title'];

    protected function getActivityTitleAttribute(): string
    {
        return 'title';
    }

    public function getSnSubjectId(): int
    {
        return $this->id;
    }

    public function getSnSubjectTitle(): string | HtmlString | null
    {
        return $this->title;
    }

    public function getSnSubjectDescription(): string | HtmlString | null
    {
        return null;
    }

    public function getSnSubjectCoverUrl(): string | HtmlString | null
    {
        return null;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', CompositionStatus::Draft);
    }

    public function scopePublished($query)
    {
        return $query->where('status', CompositionStatus::Published);
    }

    public function scopeHidden($query)
    {
        return $query->where('status', CompositionStatus::Hidden);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }
}
