<?php

namespace Wsmallnews\Support\Filament\Forms\Fields;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use League\Flysystem\UnableToCheckFileExistence;
use Plank\Mediable\MediableInterface;
use Plank\Mediable\Media;
use Throwable;
use Wsmallnews\Support\Filament\Concerns\HasMediaFilter;
// use Filament\Support\Concerns\HasMediaFilter;
// use Spatie\MediaLibrary\MediaCollections\FileAdder;
// use Spatie\MediaLibrary\MediaCollections\MediaCollection;
// use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediableFileUpload extends FileUpload
{
    use HasMediaFilter;

    protected string | Closure | null $tag = null;

    protected array | Closure | null $extensions = null;

    protected array | Closure | null $aggregateTypes = null;

    protected array | Closure | null $customHeaders = null;


    protected function setUp(): void
    {
        parent::setUp();

        $this->loadStateFromRelationshipsUsing(static function (MediableFileUpload $component, MediableInterface $record): void {
            /** @var Model&HasMedia $record */
            $media = $record->load('media')->getMedia($component->getTag() ?? 'default')
                ->when(
                    $component->hasMediaFilter(),
                    fn(Collection $media) => $component->filterMedia($media)
                )
                ->when(
                    ! $component->isMultiple(),
                    fn(Collection $media): Collection => $media->take(1),
                )
                ->mapWithKeys(function (Media $media): array {
                    $id = $media->getAttributeValue('id');

                    return [$id => $id];
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
            $media = $component->getRecord()->getRelationValue('media')->firstWhere('uuid', $file);

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

            // if ($component->getConversion() && $media?->hasGeneratedConversion($component->getConversion())) {
            //     $url ??= $media->getUrl($component->getConversion());
            // }

            $url ??= $media?->getUrl();

            return [
                'name' => $media?->getAttributeValue('name') ?? $media?->getAttributeValue('file_name'),
                'size' => $media?->getAttributeValue('size'),
                'type' => $media?->getAttributeValue('mime_type'),
                'url' => $url,
            ];
        });

        $this->saveRelationshipsUsing(static function (MediableFileUpload $component) {
            $component->deleteAbandonedFiles();
            $component->saveUploadedFiles();
        });

        $this->saveUploadedFileUsing(static function (MediableFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if (! method_exists($record, 'addMediaFromString')) {
                return $file;
            }

            try {
                if (! $file->exists()) {
                    return null;
                }
            } catch (UnableToCheckFileExistence $exception) {
                return null;
            }

            /** @var FileAdder $mediaAdder */
            $mediaAdder = $record->addMediaFromString($file->get());

            $filename = $component->getUploadedFileNameForStorage($file);

            $media = $mediaAdder
                ->addCustomHeaders($component->getCustomHeaders())
                ->usingFileName($filename)
                ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                ->withCustomProperties($component->getCustomProperties())
                ->withManipulations($component->getManipulations())
                ->withResponsiveImagesIf($component->hasResponsiveImages())
                ->withProperties($component->getProperties())
                ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

            return $media->getAttributeValue('uuid');
        });

        $this->reorderUploadedFilesUsing(static function (MediableFileUpload $component, ?Model $record, array $state): array {
            $uuids = array_filter(array_values($state));

            $mediaClass = ($record && method_exists($record, 'getMediaModel')) ? $record->getMediaModel() : null;
            $mediaClass ??= config('media-library.media_model', Media::class);

            $mappedIds = $mediaClass::query()->whereIn('uuid', $uuids)->pluck(app($mediaClass)->getKeyName(), 'uuid')->toArray();

            $mediaClass::setNewOrder([
                ...array_flip($uuids),
                ...$mappedIds,
            ]);

            return $state;
        });
    }



    public function deleteAbandonedFiles(): void
    {
        /** @var Model&MediableInterface $record */
        $record = $this->getRecord();

        $record
            ->getMedia($this->getTag() ?? 'default')
            ->whereNotIn('id', array_keys($this->getState() ?? []))
            ->when($this->hasMediaFilter(), fn(Collection $media): Collection => $this->filterMedia($media))
            ->each(fn(Media $media) => $media->delete());
    }


    // use HasMediaFilter;


    // protected string | Closure | null $conversion = null;

    // protected string | Closure | null $conversionsDisk = null;

    // protected bool | Closure $hasResponsiveImages = false;

    // protected string | Closure | null $mediaName = null;

    // /**
    //  * @var array<string, mixed> | Closure | null
    //  */
    // protected array | Closure | null $customHeaders = null;

    // /**
    //  * @var array<string, mixed> | Closure | null
    //  */
    // protected array | Closure | null $customProperties = null;

    // /**
    //  * @var array<string, array<string, string>> | Closure | null
    //  */
    // protected array | Closure | null $manipulations = null;

    // /**
    //  * @var array<string, mixed> | Closure | null
    //  */
    // protected array | Closure | null $properties = null;

    // protected function setUp(): void
    // {
    //     parent::setUp();

    //     $this->loadStateFromRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component, HasMedia $record): void {
    //         /** @var Model&HasMedia $record */
    //         $media = $record->load('media')->getMedia($component->getCollection() ?? 'default')
    //             ->when(
    //                 $component->hasMediaFilter(),
    //                 fn (Collection $media) => $component->filterMedia($media)
    //             )
    //             ->when(
    //                 ! $component->isMultiple(),
    //                 fn (Collection $media): Collection => $media->take(1),
    //             )
    //             ->mapWithKeys(function (Media $media): array {
    //                 $uuid = $media->getAttributeValue('uuid');

    //                 return [$uuid => $uuid];
    //             })
    //             ->toArray();

    //         $component->state($media);
    //     });

    //     $this->afterStateHydrated(static function (BaseFileUpload $component, string | array | null $state): void {
    //         if (is_array($state)) {
    //             return;
    //         }

    //         $component->state([]);
    //     });

    //     $this->beforeStateDehydrated(null);

    //     $this->dehydrated(false);

    //     $this->getUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): ?array {
    //         if (! $component->getRecord()) {
    //             return null;
    //         }

    //         /** @var ?Media $media */
    //         $media = $component->getRecord()->getRelationValue('media')->firstWhere('uuid', $file);

    //         $url = null;

    //         if ($component->getVisibility() === 'private') {
    //             $conversion = $component->getConversion();

    //             try {
    //                 $url = $media?->getTemporaryUrl(
    //                     now()->addMinutes(5),
    //                     (filled($conversion) && $media->hasGeneratedConversion($conversion)) ? $conversion : '',
    //                 );
    //             } catch (Throwable $exception) {
    //                 // This driver does not support creating temporary URLs.
    //             }
    //         }

    //         if ($component->getConversion() && $media?->hasGeneratedConversion($component->getConversion())) {
    //             $url ??= $media->getUrl($component->getConversion());
    //         }

    //         $url ??= $media?->getUrl();

    //         return [
    //             'name' => $media?->getAttributeValue('name') ?? $media?->getAttributeValue('file_name'),
    //             'size' => $media?->getAttributeValue('size'),
    //             'type' => $media?->getAttributeValue('mime_type'),
    //             'url' => $url,
    //         ];
    //     });

    //     $this->saveRelationshipsUsing(static function (SpatieMediaLibraryFileUpload $component) {
    //         $component->deleteAbandonedFiles();
    //         $component->saveUploadedFiles();
    //     });

    //     $this->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
    //         if (! method_exists($record, 'addMediaFromString')) {
    //             return $file;
    //         }

    //         try {
    //             if (! $file->exists()) {
    //                 return null;
    //             }
    //         } catch (UnableToCheckFileExistence $exception) {
    //             return null;
    //         }

    //         /** @var FileAdder $mediaAdder */
    //         $mediaAdder = $record->addMediaFromString($file->get());

    //         $filename = $component->getUploadedFileNameForStorage($file);

    //         $media = $mediaAdder
    //             ->addCustomHeaders($component->getCustomHeaders())
    //             ->usingFileName($filename)
    //             ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
    //             ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
    //             ->withCustomProperties($component->getCustomProperties())
    //             ->withManipulations($component->getManipulations())
    //             ->withResponsiveImagesIf($component->hasResponsiveImages())
    //             ->withProperties($component->getProperties())
    //             ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

    //         return $media->getAttributeValue('uuid');
    //     });

    //     $this->reorderUploadedFilesUsing(static function (SpatieMediaLibraryFileUpload $component, ?Model $record, array $state): array {
    //         $uuids = array_filter(array_values($state));

    //         $mediaClass = ($record && method_exists($record, 'getMediaModel')) ? $record->getMediaModel() : null;
    //         $mediaClass ??= config('media-library.media_model', Media::class);

    //         $mappedIds = $mediaClass::query()->whereIn('uuid', $uuids)->pluck(app($mediaClass)->getKeyName(), 'uuid')->toArray();

    //         $mediaClass::setNewOrder([
    //             ...array_flip($uuids),
    //             ...$mappedIds,
    //         ]);

    //         return $state;
    //     });
    // }

    public function tag(string | Closure | null $tag): static
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

    public function conversion(string | Closure | null $conversion): static
    {
        $this->conversion = $conversion;

        return $this;
    }

    public function conversionsDisk(string | Closure | null $disk): static
    {
        $this->conversionsDisk = $disk;

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
     * @param  array<string, array<string, string>> | Closure | null  $manipulations
     */
    public function manipulations(array | Closure | null $manipulations): static
    {
        $this->manipulations = $manipulations;

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

    public function responsiveImages(bool | Closure $condition = true): static
    {
        $this->hasResponsiveImages = $condition;

        return $this;
    }

    

    public function getDiskName(): string
    {
        if ($diskName = $this->evaluate($this->diskName)) {
            return $diskName;
        }

        /** @var Model&HasMedia $model */
        $model = $this->getModelInstance();

        $collection = $this->getCollection() ?? 'default';

        /** @phpstan-ignore-next-line */
        $diskNameFromRegisteredConversions = $model
            ->getRegisteredMediaCollections()
            ->filter(fn (MediaCollection $mediaCollection): bool => $mediaCollection->name === $collection)
            ->first()
            ?->diskName;

        return $diskNameFromRegisteredConversions ?? config('filament.default_filesystem_disk');
    }

    public function getTag(): ?string
    {
        return $this->evaluate($this->tag);
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


    public function getConversion(): ?string
    {
        return $this->evaluate($this->conversion);
    }

    public function getConversionsDisk(): ?string
    {
        return $this->evaluate($this->conversionsDisk);
    }



    /**
     * @return array<string, mixed>
     */
    public function getCustomProperties(): array
    {
        return $this->evaluate($this->customProperties) ?? [];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getManipulations(): array
    {
        return $this->evaluate($this->manipulations) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->evaluate($this->properties) ?? [];
    }

    public function hasResponsiveImages(): bool
    {
        return (bool) $this->evaluate($this->hasResponsiveImages);
    }

    public function mediaName(string | Closure | null $name): static
    {
        $this->mediaName = $name;

        return $this;
    }

    public function getMediaName(TemporaryUploadedFile $file): ?string
    {
        return $this->evaluate($this->mediaName, [
            'file' => $file,
        ]);
    }
}
