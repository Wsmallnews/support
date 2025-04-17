<?php

namespace Wsmallnews\Support\Filament\Tables\Columns;

use Closure;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Plank\Mediable\Media;
use Throwable;
use Wsmallnews\Support\Filament\Concerns\HasMediaFilter;

class MediableImageColumn extends ImageColumn
{
    use HasMediaFilter;

    protected string | Closure | null $tag = 'default';

    protected string | Closure | null $variant = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 图片未找到时候的默认图
        $this->defaultImageUrl(function (MediableImageColumn $column, Model $record): ?string {
            if ($column->hasRelationship($record)) {
                $record = $column->getRelationshipResults($record);
            }

            $records = Arr::wrap($record);

            $tag = $column->getTag();

            // @sn todo 这里默认图未处理，如果图片未找到，将显示 烂掉的图片
            // foreach ($records as $record) {
            //     $url = $record->getFallbackMediaUrl($tag, $column->getConversion() ?? '');

            //     if (blank($url)) {
            //         continue;
            //     }

            //     return $url;
            // }

            return null;
        });
    }

    /**
     * 获取指定图片地址
     */
    public function getImageUrl(?string $state = null): ?string
    {
        $record = $this->getRecord();

        if ($this->hasRelationship($record)) {
            $record = $this->getRelationshipResults($record);
        }

        $records = Arr::wrap($record);

        foreach ($records as $record) {
            /** @var Model $record */

            /** @var ?Media $media */
            $media = $record->getRelationValue('media')->first(fn (Media $media): bool => $this->compareUniqueId($media, $state));

            if (! $media) {
                continue;
            }

            $variant = $this->getVariant();

            if ($this->getVisibility() === 'private') {
                try {
                    if ($variant) {
                        return $media->findVariant($variant)?->getTemporaryUrl(
                            now()->addMinutes(5),
                        );
                    }

                    return $media->getTemporaryUrl(
                        now()->addMinutes(5),
                    );
                } catch (Throwable $exception) {
                    // This driver does not support creating temporary URLs.
                }
            }

            if ($variant) {
                return $media->findVariant($variant)?->getUrl();
            }

            return $media->getUrl();
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getState(): array
    {
        return $this->cacheState(function (): array {
            $record = $this->getRecord();

            if ($this->hasRelationship($record)) {
                $record = $this->getRelationshipResults($record);
            }

            $records = Arr::wrap($record);

            $state = [];

            $tag = $this->getTag();

            foreach ($records as $record) {
                /** @var Model $record */
                $state = [
                    ...$state,
                    ...$record->getRelationValue('media')
                        ->filter(fn (Media $media): bool => $media->pivot->tag === $tag)
                        ->when(
                            $this->hasMediaFilter(),
                            fn (Collection $media) => $this->filterMedia($media)
                        )
                        ->map(function (Media $media) {
                            return $this->getUniqueId($media);
                        })
                        ->all(),
                ];
            }

            return array_unique($state);
        });
    }

    /**
     * table 查询时，追加查询条件
     */
    public function applyEagerLoading(Builder | Relation $query): Builder | Relation
    {
        if ($this->isHidden()) {
            return $query;
        }

        /** @phpstan-ignore-next-line */
        $modifyMediaQuery = fn (Builder | Relation $query) => $query->with('variants');     // 这里不能追加 tag 查询条件(->wherePivot('tag', $this->getTag()))，table 是整体来查的，如果有多个字段要显示图片，就会有问题

        if ($this->hasRelationship($query->getModel())) {
            return $query->with([
                "{$this->getRelationshipName()}.media" => $modifyMediaQuery,
            ]);
        }

        return $query->with(['media' => $modifyMediaQuery]);
    }

    /**
     * 给图片资源生成一个全新的唯一 id，标记图片用，不存库
     *
     * @param  Media  $media
     * @return string
     */
    private function getUniqueId($media)
    {
        return md5($media->disk . '-' . $media->directory . '-' . $media->filename . '-' . $media->extension);
    }

    private function compareUniqueId($media, $key): bool
    {
        return $this->getUniqueId($media) === $key;
    }

    public function tag(string | Closure $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function variant(string | Closure | null $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getTag(): string
    {
        return $this->evaluate($this->tag) ?: 'default';
    }

    public function getVariant(): ?string
    {
        return $this->evaluate($this->variant);
    }
}
