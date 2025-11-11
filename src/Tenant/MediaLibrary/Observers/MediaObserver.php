<?php

namespace Wsmallnews\Support\Tenant\MediaLibrary\Observers;

use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function creating(Media $media): void
    {
        if ($media->shouldSortWhenCreating()) {
            if (is_null($media->order_column)) {
                $media->setHighestOrderNumber();
            }
        }
    }

    public function updating(Media $media): void
    {
        /** @var Filesystem $filesystem */
        $filesystem = app(Filesystem::class);

        if (config('media-library.moves_media_on_update')) {
            $filesystem->syncMediaPath($media);
        }

        if ($media->file_name !== $media->getOriginal('file_name')) {
            $filesystem->syncFileNames($media);
        }
    }

    public function updated(Media $media): void
    {
        if (is_null($media->getOriginal('model_id'))) {
            return;
        }

        $original = $media->getOriginal('manipulations');

        if ($media->manipulations !== $original) {
            $eventDispatcher = Media::getEventDispatcher();
            Media::unsetEventDispatcher();

            /** @var FileManipulator $fileManipulator */
            $fileManipulator = app(FileManipulator::class);

            $fileManipulator->createDerivedFiles($media);

            Media::setEventDispatcher($eventDispatcher);
        }
    }

    public function deleted(Media $media): void
    {
        if (method_exists($media, 'isForceDeleting') && ! $media->isForceDeleting()) {
            return;
        }

        // 只删除数据库记录，不删除文件 (比如：删除产品时，产品图片还需要在订单 item 中使用)

        /** @var Filesystem $filesystem */
        // $filesystem = app(Filesystem::class);

        // $filesystem->removeAllFiles($media);
    }
}
