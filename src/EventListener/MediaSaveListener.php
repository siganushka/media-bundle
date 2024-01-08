<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\ChannelInterface;
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
    private StorageInterface $storage;
    private MediaRepository $mediaRepository;

    public function __construct(StorageInterface $storage, MediaRepository $mediaRepository)
    {
        $this->storage = $storage;
        $this->mediaRepository = $mediaRepository;
    }

    public function onMediaSave(MediaSaveEvent $event): void
    {
        $channel = $event->getChannel();
        $file = $event->getFile();
        $ref = $event->getRef();

        $media = $this->mediaRepository->findOneByRef($ref);
        if (null === $media) {
            $media = $this->saveFile($channel, $file, $ref);
        }

        $event->setMedia($media)->stopPropagation();
    }

    protected function saveFile(ChannelInterface $channel, File $file, string $ref): Media
    {
        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable $th) {
            $width = $height = null;
        }

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $size = FileUtils::getFormattedSize($file);

        // pre save hook
        $channel->onPreSave($file);

        // save to storage
        $mediaUrl = $this->storage->save($channel, $file);

        // post save hook
        $channel->onPostSave($mediaUrl);

        $media = $this->mediaRepository->createNew();
        $media->setRef($ref);
        $media->setChannel($channel);
        $media->setName($name);
        $media->setUrl($mediaUrl);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);

        return $media;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaSaveEvent::class => 'onMediaSave',
        ];
    }
}
