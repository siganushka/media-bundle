<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\NamingStrategy;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsEventListener(priority: -8)]
class MediaSaveListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MediaRepository $repository,
        private readonly NamingStrategy $naming,
    ) {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $file = $event->getFile();

        // [important] Clears file status cache before access file.
        clearstatcache(true, $file->getPathname());

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $normalizedName = FileUtils::normalizeFilename($name);
        $extension = $file->guessExtension() ?? $file->getExtension();
        $mime = $file->getMimeType() ?? throw new \RuntimeException('Unable to get mime type.');
        $size = $file->getSize() ?: 0;

        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable) {
            $width = $height = null;
        }

        $targetFile = $this->naming->getTargetFile($event);
        $url = $this->storage->save($file, $targetFile);

        $media = $event->getMedia() ?? $this->repository->createNew();
        $media->setHash($event->getHash());
        $media->setName($normalizedName);
        $media->setExtension($extension);
        $media->setMime($mime);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setUrl($url);

        $event->setMedia($media);
    }
}
