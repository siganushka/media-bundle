<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\StorageInterface;

class MediaRemoveListener
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function postRemove(Media $entity): void
    {
        $url = $entity->getUrl();
        if (\is_string($url)) {
            $this->storage->delete($url);
        }
    }
}
