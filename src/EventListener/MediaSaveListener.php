<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaSaveListener implements EventSubscriberInterface
{
    public function __construct(private readonly StorageInterface $storage, private readonly MediaRepository $mediaRepository)
    {
    }

    public function onMediaSave(MediaSaveEvent $event): void
    {
        $channel = $event->getChannel();
        $file = $event->getFile();

        // pre save hook
        $channel->onPreSave($file);

        // save file if not exists
        $media = $this->saveFile($channel, $file);

        // stop propagation for event
        $event->setMedia($media)->stopPropagation();
    }

    protected function saveFile(ChannelInterface $channel, File $file): Media
    {
        $hash = md5_file($file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        $media = $this->mediaRepository->findOneByHash($hash);
        if ($media instanceof Media) {
            return $media;
        }

        // [important] Clears file status cache before access file
        clearstatcache(true, $file->getPathname());

        $imagesize = @getimagesize($file->getPathname());
        $width = \is_array($imagesize) ? $imagesize[0] : null;
        $height = \is_array($imagesize) ? $imagesize[1] : null;

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $extension = $file->guessExtension() ?? $file->getExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();
        if (false === $size || null === $mimeType) {
            throw new \RuntimeException('Unable to access file.');
        }

        $media = $this->mediaRepository->createNew();
        $media->setHash($hash);
        $media->setName($name);
        $media->setExtension($extension);
        $media->setMimeType($mimeType);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);

        // save to storage
        $mediaUrl = $this->storage->save($channel, $file);

        // post save hook
        $channel->onPostSave($mediaUrl);

        return $media->setUrl($mediaUrl);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaSaveEvent::class => 'onMediaSave',
        ];
    }
}
