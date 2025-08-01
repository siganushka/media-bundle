<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    private ?string $hash = null;
    private ?Media $media = null;

    public function __construct(private readonly Channel $channel, private readonly \SplFileInfo $file)
    {
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
        if (isset($this->hash)) {
            return $this->hash;
        }

        $fileHash = md5_file($this->file->getPathname()) ?: throw new \RuntimeException('Unable to hash file.');
        $channelHash = \sprintf('%s_%32s', $this->channel->alias, $fileHash);

        // [important] The same source file will generate different HASH in different channels.
        return $this->hash = md5($channelHash);
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
