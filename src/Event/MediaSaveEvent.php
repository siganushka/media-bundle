<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Model\MediaInterface;

class MediaSaveEvent extends MediaEvent
{
    private ?MediaInterface $media = null;

    public function getMedia(): ?MediaInterface
    {
        return $this->media;
    }

    public function setMedia(?MediaInterface $media): static
    {
        $this->media = $media;

        return $this;
    }
}
