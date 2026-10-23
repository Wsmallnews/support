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
 * Site page entity: the single source of truth for which pages exist. Content is either a
 * bound composition or its own content, and navigation points at pages rather than content.
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

    /** Searchable fields for morph keyword search. */
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

    /** Find by slug, unique within the scope. */
    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function composition(): BelongsTo
    {
        return $this->belongsTo(Utils::getCompositionModel());
    }

    /** Own content channel, used when no composition is bound. */
    public function content(): MorphOne
    {
        return $this->morphOne(Utils::getContentModel(), 'contentable');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Utils::getTenantModel());
    }

    /**
     * Resolve the composition channel into renderable rows; an invalid binding returns an
     * empty array so the caller can fall back to content or the empty state.
     *
     * @return array CompositionRenderer::resolveRows() output
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
