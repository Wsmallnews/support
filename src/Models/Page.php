<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Contracts\HasSnSubject;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Features\Composition\CompositionRenderer;
use Wsmallnews\Support\Models\Concerns\HasActivityLog;
use Wsmallnews\Support\Models\Concerns\HasOrderColumn;
use Wsmallnews\Support\Support\Utils;

/**
 * 站点页面：站点静态内容页实体（关于我们/政策/专题页），是"这个站点有哪些页面"的唯一事实源。
 * 内容双通道互斥（编排优先）：composition_id 绑定编排（复杂页面的高级模式），
 * 或 content 关联承载自有内容（富文本/Markdown，简单页面的主体通道）；导航指向页面而非承载页面。
 * 侧栏相关字段（sidebar_purpose / sidebar_position）在 purpose 机制落地时追加。
 */
class Page extends SupportModel implements HasSnSubject
{
    use HasActivityLog;
    use HasOrderColumn;
    use SoftDeletes;

    protected $table = 'sn_pages';

    protected $casts = [
        'status' => PageStatus::class,
        'options' => 'array',
    ];

    /**
     * 搜索字段（用于 morphFilter 关键词搜索）。
     */
    public static array $keywordSearchFields = ['title', 'slug'];

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
        return $this->slug;
    }

    public function getSnSubjectCoverUrl(): string | HtmlString | null
    {
        return null;
    }

    public function scopeDraft($query)
    {
        return $query->where('status', PageStatus::Draft);
    }

    public function scopePublished($query)
    {
        return $query->where('status', PageStatus::Published);
    }

    public function scopeHidden($query)
    {
        return $query->where('status', PageStatus::Hidden);
    }

    /**
     * 按 slug 查询（scope 内唯一）
     */
    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Utils::getCompositionModel());
    }

    /**
     * 页面自有内容（未绑定编排时的主体通道，复用 sn_contents 多态体系）
     */
    public function content(): MorphOne
    {
        return $this->morphOne(Utils::getContentModel(), 'contentable');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }

    /**
     * 编排通道解析：页面 → 可渲染的编排行
     *
     * 绑定失效（未绑 / 编排被删 / 草稿）返回空数组，由渲染层走 content 通道或空态；
     * $module 为 CompositionRegistry 的组件来源模块（插件 id），$pageContext 为页面级上下文种子
     *
     * @return array CompositionRenderer::resolveRows() 输出
     */
    public function resolveRows(string $module, array $pageContext = []): array
    {
        $composition = $this->composition_id
            ? Utils::getCompositionModel()::query()
                ->published()
                ->find($this->composition_id)
            : null;

        if (! $composition) {
            return [];
        }

        return CompositionRenderer::resolveRows($composition->components, $module, $pageContext);
    }
}
