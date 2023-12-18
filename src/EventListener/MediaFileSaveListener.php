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

        $path = $file->getPathname();
        $size = $file->getSize();

        // try to fetch width & height
        [$width, $height] = @getimagesize($path);

        // pre save hook
        $channel->onPreSave($file);

        // save to storage
        $mediaUrl = $this->storage->save($channel, $file);

        // post save hook
        $channel->onPostSave($mediaUrl);

        $media = $this->mediaRepository->createNew();
        $media->setHash($event->getFileHash());
        $media->setChannel($channel->getAlias());
        $media->setName(pathinfo($mediaUrl, \PATHINFO_BASENAME));
        $media->setSize(self::formatBytes($size));
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

    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0B';
        }

        $base = log($bytes, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];

        return round(1024 ** ($base - floor($base)), 2).($suffixes[(int) floor($base)] ?? '');
    }
}
