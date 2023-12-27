<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    private ChannelInterface $channel;
    private File $file;

    private ?Media $media = null;

    public function __construct(ChannelInterface $channel, File $file)
    {
        $this->channel = $channel;
        $this->file = $file;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getHash(): string
    {
        $hash = hash_file('MD5', $this->file->getPathname());
        if (!$hash) {
            throw new \RuntimeException('Unable to access file.');
        }

        return hash('MD5', sprintf('%32s%s', $hash, (string) $this->channel));
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): self
    {
        $this->media = $media;

        return $this;
    }
}