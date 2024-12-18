<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;

abstract class AbstractMediaEvent extends AbstractFileEvent
{
    private ?Media $media = null;

    final public function __construct(private readonly Channel $channel, \SplFileInfo $file)
    {
        parent::__construct($file);
    }

    public function getChannel(): Channel
    {
        return $this->channel;
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
