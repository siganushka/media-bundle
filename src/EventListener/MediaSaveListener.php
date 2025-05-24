<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsEventListener(MediaSaveEvent::class, method: 'check', priority: 128)]
#[AsEventListener(MediaSaveEvent::class, method: 'save', priority: -128)]
class MediaSaveListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MediaRepository $repository)
    {
    }

    public function check(MediaSaveEvent $event): void
    {
        $media = $this->repository->findOneByHash($event->getHash());
        if ($media instanceof Media) {
            // Remove the original file if it already exists
            $file = $event->getFile();
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }

            $event->setMedia($media)->stopPropagation();
        }
    }

    public function save(MediaSaveEvent $event): void
    {
        $file = $event->getFile();
        if (!$file instanceof File) {
            $file = new File($file->getPathname());
        }

        // [important] Clears file status cache before access file
        clearstatcache(true, $file->getPathname());

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        if ($replace = mb_substr(pathinfo($name, \PATHINFO_FILENAME), 32)) {
            $name = str_replace($replace, '', $name);
        }

        $extension = $file->guessExtension() ?? $file->getExtension();
        $mime = $file->getMimeType();
        if (null === $mime) {
            throw new \RuntimeException('Unable to access file.');
        }

        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable) {
            $width = $height = null;
        }

        $media = $this->repository->createNew();
        $media->setHash($event->getHash());
        $media->setName($name);
        $media->setExtension($extension);
        $media->setMime($mime);
        $media->setSize(FileUtils::getFormattedSize($file));
        $media->setWidth($width);
        $media->setHeight($height);

        $mediaUrl = $this->storage->save($file, $event->getChannel()->getTargetName($file));
        $media->setUrl($mediaUrl);

        $event->setMedia($media)->stopPropagation();
    }
}
