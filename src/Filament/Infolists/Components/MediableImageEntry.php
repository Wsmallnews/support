<?php

namespace Wsmallnews\Support\Filament\Infolists\Components;

use Closure;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Plank\Mediable\Media;
use Throwable;
use Wsmallnews\Support\Filament\Concerns\HasMediaFilter;

class MediableImageEntry extends ImageEntry
{
    use HasMediaFilter;

    protected string | Closure | null $tag = 'default';

    protected string | Closure | null $variant = null;


    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultImageUrl(function (MediableImageEntry $component, Model $record): ?string {
            if ($component->hasRelationship($record)) {
                $record = $component->getRelationshipResults($record);
            }

            $records = Arr::wrap($record);

            $tag = $component->getTag();

            // @sn todo 这里默认图未处理，如果图片未找到，将显示 烂掉的图片
            // foreach ($records as $record) {
            //     $url = $record->getFallbackMediaUrl($collection, $component->getConversion() ?? '');

            //     if (blank($url)) {
            //         continue;
            //     }

            //     return $url;
            // }

            return null;
        });
    }

    

    public function getImageUrl(?string $state = null): ?string
    {
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

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
                    ->filter(fn(Media $media): bool => $media->pivot->tag === $tag)
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
    }


    /**
     * 给图片资源生成一个全新的唯一 id，标记图片用，不存库
     *
     * @param Media $media
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
