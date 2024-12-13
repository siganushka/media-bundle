<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaSaveListener implements EventSubscriberInterface
{
    public function __construct(private readonly StorageInterface $storage, private readonly MediaRepository $repository)
    {
    }

    public function onMediaSave(MediaSaveEvent $event): void
    {
        $file = $event->getFile();

        $hash = md5_file($file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        $media = $this->repository->findOneByHash($hash);
        if ($media instanceof Media) {
            goto SET_EVENT_DATA;
        }

        $channel = $event->getChannel();
        $channel->onPreSave($file);

        // [important] Clears file status cache before access file
        clearstatcache(true, $file->getPathname());

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $extension = $file->guessExtension() ?? $file->getExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        if (false === $size || null === $mimeType) {
            throw new \RuntimeException('Unable to access file.');
        }

        [$width, $height] = @getimagesize($file->getPathname()) ?: [null, null];

        $media = $this->repository->createNew();
        $media->setHash($hash);
        $media->setName($name);
        $media->setExtension($extension);
        $media->setMimeType($mimeType);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);

        $mediaUrl = $this->storage->save($file, $channel->getTargetName($file));
        $channel->onPostSave($mediaUrl);

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
