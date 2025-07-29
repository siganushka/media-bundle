<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\Response;

class MediaSaveSuccessEvent extends MediaEvent
{
    private ?Response $response = null;

    public function __construct(Channel $channel, \SplFileInfo $file, private readonly Media $media)
    {
        parent::__construct($channel, $file);
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
