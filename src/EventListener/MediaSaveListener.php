<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaSaveListener implements EventSubscriberInterface
{
    public function __construct(private readonly StorageInterface $storage, private readonly MediaRepository $repository)
    {
    }

    public function onMediaSave(MediaSaveEvent $event): void
    {
        $file = $event->getFile();
        if (!$file instanceof File) {
            $file = new File($file->getPathname());
        }

        $hash = hash_file('MD5', $file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        $media = $this->repository->findOneByHash($hash);
        if ($media instanceof Media) {
            goto SET_EVENT_DATA;
        }

        // [important] Clears file status cache before access file
        clearstatcache(true, $file->getPathname());

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
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

        $size = FileUtils::getFormattedSize($file);

        $media = $this->repository->createNew();
        $media->setHash($hash);
        $media->setName($name);
        $media->setExtension($extension);
        $media->setMime($mime);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);

        $mediaUrl = $this->storage->save($file, $event->getChannel()->getTargetName($file));
        $media->setUrl($mediaUrl);

        SET_EVENT_DATA:
        $event->setMedia($media)->stopPropagation();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaSaveEvent::class => 'onMediaSave',
        ];
    }
}
