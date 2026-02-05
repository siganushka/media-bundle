<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\StorageInterface;

class MediaListener
{
    public function __construct(private readonly StorageInterface $storage)
    {
    }

    public function __invoke(Media $entity): void
    {
        $url = $entity->getUrl();
        if (!\is_string($url)) {
            return;
        }

        try {
            $this->storage->delete($url);
        } catch (\Throwable) {
        }
    }
}
