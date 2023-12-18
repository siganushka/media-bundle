<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\Event;

class MediaFileSaveEvent extends Event
{
    private ChannelInterface $channel;
    private File $file;
    private string $fileHash;

    private ?Media $media = null;

    public function __construct(ChannelInterface $channel, File $file, string $fileHash)
    {
        $this->channel = $channel;
        $this->file = $file;
        $this->fileHash = $fileHash;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getFileHash(): string
    {
        return $this->fileHash;
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
