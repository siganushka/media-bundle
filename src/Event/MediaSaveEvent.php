<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Entity\Media;

class MediaSaveEvent extends MediaEvent
{
    private ?Media $media = null;

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
