<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine\EventListener;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\StorageInterface;

class MediaRemoveListener
{
    public function __construct(private LoggerInterface $logger, private StorageInterface $storage)
    {
    }

    public function postRemove(Media $entity, PostRemoveEventArgs $args): void
    {
        $url = $entity->getUrl();
        if (null === $url) {
            return;
        }

        try {
            $this->storage->delete($url);
        } catch (\Throwable $th) {
            $this->logger->debug('fail to delete file.', ['message' => $th->getMessage(), 'url' => $url]);
        }
    }
}
