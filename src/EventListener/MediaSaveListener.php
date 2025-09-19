<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\File;

#[AsEventListener(priority: -8)]
class MediaSaveListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MediaRepository $repository)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $file = $event->getFile();
        if (!$file instanceof File) {
            $file = new File($file->getPathname());
        }

        // [important] Clears file status cache before access file.
        clearstatcache(true, $file->getPathname());

        $normalizedName = FileUtils::getNormalizedName($file);
        $extension = $file->guessExtension() ?? $file->getExtension();
        $mime = $file->getMimeType() ?? 'n/a';
        $size = $file->getSize() ?: 0;

        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable) {
            $width = $height = null;
        }

        $targetFileName = $event->getRule()->reserveClientName
            ? $normalizedName
            : \sprintf('%07s.%s', mb_substr($event->getHash(), 0, 7), $extension);

        $targetFile = \sprintf('%02s/%02s/%s',
            mb_substr($event->getHash(), 0, 2),
            mb_substr($event->getHash(), 2, 2),
            $targetFileName);

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
