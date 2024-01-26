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

    /**
     * @throws \RuntimeException Fail to hash file
     */
    public function getHash(): string
    {
        $hash = md5_file($this->file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        return $hash;
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
