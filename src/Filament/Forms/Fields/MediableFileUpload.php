<?php

namespace Wsmallnews\Support\Filament\Forms\Fields;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Plank\Mediable\Facades\MediaUploader;
use Plank\Mediable\Jobs\CreateImageVariants;
use Plank\Mediable\Media;
use Plank\Mediable\MediableInterface;
use Throwable;
use Wsmallnews\Support\Filament\Concerns\HasMediaFilter;

class MediableFileUpload extends FileUpload
{
    use HasMediaFilter;

    protected string | Closure | null $tag = 'default';

    protected array | Closure | null $extensions = null;

    protected array | Closure | null $aggregateTypes = null;

    protected array | Closure | null $customHeaders = null;

    /**
     * @var array<string, mixed> | Closure | null
     */
    protected array | Closure | null $customProperties = null;

    /**
     * @var array<string, mixed> | Closure | null
     */
    protected array | Closure | null $properties = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadStateFromRelationshipsUsing(static function (MediableFileUpload $component, MediableInterface $record): void {
            /** @var Model&MediableInterface $record */
            $media = $record->getMedia([$component->getTag()])
                ->when(
                    $component->hasMediaFilter(),
                    fn (Collection $media) => $component->filterMedia($media)
                )
                ->when(
                    ! $component->isMultiple(),
                    fn (Collection $media): Collection => $media->take(1),
                )
                ->mapWithKeys(function (Media $media) use ($component): array {
                    $unique_id = $component->getUniqueId($media);

                    return [$unique_id => $unique_id];
                })
                ->toArray();

            $component->state($media);
        });

        $this->afterStateHydrated(static function (MediableFileUpload $component, string | array | null $state): void {
            if (is_array($state)) {
                return;
            }

            $component->state([]);
        });

        $this->beforeStateDehydrated(null);

        $this->dehydrated(false);

        $this->getUploadedFileUsing(static function (MediableFileUpload $component, string $file): ?array {
            if (! $component->getRecord()) {
                return null;
            }

            /** @var ?Media $media */
            $media = $component->getRecord()->getRelationValue('media')->first(function ($media) use ($component, $file) {
                return $component->compareUniqueId($media, $file);
            });

            $url = null;

            if ($component->getVisibility() === 'private') {
                try {
                    $url = $media?->getTemporaryUrl(
                        now()->addMinutes(5)
                    );
                } catch (Throwable $exception) {
                    // This driver does not support creating temporary URLs.
                }
            }

            $url ??= $media?->getUrl();

            return [
                'name' => $media?->getAttributeValue('alt') ?: $media?->getAttributeValue('file_name'),     // alt 是原始文件名，但是前端并未显示
                'size' => $media?->getAttributeValue('size'),
                'type' => $media?->getAttributeValue('mime_type'),
                'url' => $url,
            ];
        });

        $this->saveRelationshipsUsing(static function (MediableFileUpload $component) {
            $component->deleteAbandonedFiles($component);
            $component->saveUploadedFiles();
        });

        $this->saveUploadedFileUsing(static function (MediableFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if (! $record) {
                return $file;
            }

            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence $exception) {
                return null;
            }

            $media = MediaUploader::fromSource($file)
                ->toDestination($component->getDiskName(), $component->getDirectory() ?? '')
                ->useHashForFilename('sha1')
                ->withOptions($component->getCustomHeaders())
                ->setMaximumSize($component->getMaxSize() ?? 0)
                ->setAllowedMimeTypes($component->getAcceptedFileTypes() ?? [])
                ->setAllowedExtensions($component->getExtensions() ?? [])
                ->setAllowedAggregateTypes($component->getAggregateTypes() ?? [])
                ->withAltAttribute($file->getClientOriginalName())
                ->onDuplicateUpdate()
                // ->withCustomProperties($component->getCustomProperties())        // 待实现
                // ->beforeSave(function (Media $media, $source) use ($component) {
                //     if (method_exists($component->getLivewire(), 'getScopeInfo')) {
                //         $scopeInfo = $component->getLivewire()->getScopeInfo();
                //     }

                //     $media->setAttribute('scope_type', $scopeInfo['scope_type'] ?? 'default');
                //     $media->setAttribute('scope_id', $scopeInfo['scope_id'] ?? 0);

                //     foreach ($component->getProperties() as $key => $value) {
                //         $media->setAttribute($key, $value);
                //     }
                // })
                ->upload();

            $record->attachMedia($media, [$component->getTag()]);

            // 创建缩略图
            CreateImageVariants::dispatch($media, ['thumbnail', 'medium', 'large']);

            return $component->getUniqueId($media);
        });

        $this->reorderUploadedFilesUsing(static function (MediableFileUpload $component, ?Model $record, array $state): array {
            if (! $record) {
                return $state;
            }

            $uniqueIds = array_filter(array_values($state));
            $uniqueIdsOrder = array_flip($uniqueIds);

            // 按照 state 的顺序对 media 进行排序
            $media = $record->getMedia([$component->getTag()])->sortBy(function (Media $media) use ($uniqueIdsOrder, $component) {
                return $uniqueIdsOrder[$component->getUniqueId($media)] ?? PHP_INT_MAX;
            });

            // 同步 media 与 model 的关联
            $record->syncMedia($media, [$component->getTag()]);

            return $state;
        });
    }

    private function getUniqueId($media)
    {
        return md5($media->disk . '-' . $media->directory . '-' . $media->filename . '-' . $media->extension);
    }

    private function compareUniqueId($media, $key): bool
    {
        return $this->getUniqueId($media) === $key;
    }

    public function deleteAbandonedFiles(MediableFileUpload $component): void
    {
        /** @var Model&MediableInterface $record */
        $record = $component->getRecord();

        // 查询移除的 media
        $medias = $record->getMedia([$component->getTag()])
            ->when($this->hasMediaFilter(), fn (Collection $media): Collection => $this->filterMedia($media))
            ->filter(fn (Media $media) => ! in_array($component->getUniqueId($media), array_keys($this->getState() ?? [])));

        // 将medias 与 model 的关联删除
        $record->detachMedia($medias, [$component->getTag()]);
    }

    /**
     * 限制上传图片
     */
    public function image(): static
    {
        $this->aggregateTypes([
            Media::TYPE_IMAGE,          // 图片聚合类型
            Media::TYPE_IMAGE_VECTOR,   // 矢量图 聚合类型
        ]);

        return $this;
    }

    public function tag(string | Closure $tag): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function extensions(array | Closure | null $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function aggregateTypes(array | Closure | null $aggregateTypes): static
    {
        $this->aggregateTypes = $aggregateTypes;

        return $this;
    }

    /**
     * @param  array<string, mixed> | Closure | null  $headers
     */
    public function customHeaders(array | Closure | null $headers): static
    {
        $this->customHeaders = $headers;

        return $this;
    }

    /**
     * @param  array<string, mixed> | Closure | null  $properties
     */
    public function customProperties(array | Closure | null $properties): static
    {
        $this->customProperties = $properties;

        return $this;
    }

    /**
     * @param  array<string, mixed> | Closure | null  $properties
     */
    public function properties(array | Closure | null $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    public function getTag(): string
    {
        return $this->evaluate($this->tag) ?: 'default';
    }

    public function getExtensions(): ?array
    {
        return $this->evaluate($this->extensions);
    }

    public function getAggregateTypes(): ?array
    {
        return $this->evaluate($this->aggregateTypes);
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomHeaders(): array
    {
        return $this->evaluate($this->customHeaders) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomProperties(): array
    {
        return $this->evaluate($this->customProperties) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->evaluate($this->properties) ?? [];
    }
}
