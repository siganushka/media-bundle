<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSuccessEvent extends Event
{
    private ?Response $response = null;

    final public function __construct(
        private readonly Channel $channel,
        private readonly \SplFileInfo $file,
        private readonly Media $media)
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

    public function getMedia(): Media
    {
        return $this->media;
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
