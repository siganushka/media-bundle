<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    private ?Media $media = null;
    private string $hash;

    final public function __construct(
        private readonly Channel $channel,
        private readonly \SplFileInfo $file)
    {
        $fileHash = md5_file($file->getPathname());
        if (false === $fileHash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        // [important] The same source file will generate different HASH in different channels.
        $this->hash = md5(\sprintf('%s_%32s', $channel->__toString(), $fileHash));
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    public function getFile(): \SplFileInfo
    {
        return $this->file;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): self
    {
        $this->media = $media;

        return $this;
    }
}
