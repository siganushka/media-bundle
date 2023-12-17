<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaFileSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaFileSaveListener implements EventSubscriberInterface
{
    private StorageInterface $storage;
    private MediaRepository $mediaRepository;

    public function __construct(StorageInterface $storage, MediaRepository $mediaRepository)
    {
        $this->storage = $storage;
        $this->mediaRepository = $mediaRepository;
    }

    public function onMediaFileSave(MediaFileSaveEvent $event): void
    {
        $file = $event->getFile();

        $channel = $event->getChannel();
        $channel->onPreSave($file);

        $path = $file->getRealPath();
        $size = $file->getSize();

        // try to fetch width & height
        [$width, $height] = @getimagesize($path);

        // save to storage
        $mediaUrl = $this->storage->save($channel, $file);
        $channel->onPostSave($mediaUrl);

        $media = $this->mediaRepository->createNew();
        $media->setHash($event->getFileHash());
        $media->setChannel($channel->getAlias());
        $media->setName(pathinfo($mediaUrl, \PATHINFO_BASENAME));
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setUrl($mediaUrl);

        $event->setMedia($media)->stopPropagation();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaFileSaveEvent::class => 'onMediaFileSave',
        ];
    }
}
