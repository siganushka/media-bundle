<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\StorageInterface;

#[AsEntityListener(event: Events::postRemove, entity: Media::class)]
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
