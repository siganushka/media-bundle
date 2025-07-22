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
        if ($search = mb_substr(pathinfo($name, \PATHINFO_FILENAME), 32)) {
            $name = str_replace($search, '', $name);
        }

        $extension = $file->guessExtension() ?? $file->getExtension();
        $mime = $file->getMimeType();
        $size = $file->getSize();
        if (false === $size || null === $mime) {
            throw new \RuntimeException('Unable to access file.');
        }

        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable) {
            $width = $height = null;
        }

        $targetFileName = \sprintf('%02s/%02s/%07s.%s',
            mb_substr($event->getHash(), 0, 2),
            mb_substr($event->getHash(), 2, 2),
            mb_substr($event->getHash(), 0, 7),
            $extension);

        $url = $this->storage->save($file, $targetFileName);

        $media = $this->repository->createNew();
        $media->setHash($event->getHash());
        $media->setName($name);
        $media->setExtension($extension);
        $media->setMime($mime);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setUrl($url);

        $event->setMedia($media)->stopPropagation();
    }
}
