<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    public const MEDIA_SAVED = 'siganushka_media.saved';

    private ?string $hash = null;
    private ?Media $media = null;
    private ?Response $response = null;

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

        $fileHash = md5_file($this->file->getPathname())
            ?: throw new \RuntimeException('Unable to hash file.');

        // [important] The same source file will generate different HASH in different channels.
        return $this->hash = md5(\sprintf('%s_%32s', $this->channel->alias, $fileHash));
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

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): self
    {
        $this->response = $response;

        return $this;
    }
}
