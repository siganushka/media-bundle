<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaFileSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;

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

        $hash = hash_file('MD5', $file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        $media = $this->mediaRepository->findOneByHash($hash);
        if (null === $media) {
            $media = $this->saveFile($channel, $file, $hash);
        }

        $event->setMedia($media)->stopPropagation();
    }

    public function saveFile(ChannelInterface $channel, File $file, string $hash): Media
    {
        $size = FileUtils::getFormattedSize($file);
        [$width, $height] = FileUtils::getImageSize($file);

        // pre save hook
        $channel->onPreSave($file);

        // // save to storage
        // $mediaUrl = $this->storage->save($channel, $file);

        // post save hook
        // $channel->onPostSave($mediaUrl);

        $media = $this->mediaRepository->createNew();
        $media->setHash($hash);
        $media->setChannel($channel->getAlias());
        // $media->setName(pathinfo($mediaUrl, \PATHINFO_BASENAME));
        // $media->setUrl($mediaUrl);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);

        return $media;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaFileSaveEvent::class => 'onMediaFileSave',
        ];
    }
}
