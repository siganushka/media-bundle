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
        $channel = $event->getChannel();
        $file = $event->getFile();

        $path = $file->getRealPath();
        $size = $file->getSize();

        // try to fetch width & height
        [$width, $height] = @getimagesize($path);

        // save to storage
        $mediaUrl = $this->storage->save($channel, $file);

        $meida = $this->mediaRepository->createNew();
        $meida->setHash($event->getFileHash());
        $meida->setChannel($channel->getAlias());
        $meida->setName(pathinfo($mediaUrl, \PATHINFO_BASENAME));
        $meida->setSize($size);
        $meida->setWidth($width);
        $meida->setHeight($height);
        $meida->setUrl($mediaUrl);

        $event->setMedia($meida)->stopPropagation();
    }

    public static function getSubscribedEvents()
    {
        return [
            MediaFileSaveEvent::class => 'onMediaFileSave',
        ];
    }
}
