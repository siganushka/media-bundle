<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine\EventListener;

use Doctrine\ORM\Event\PostRemoveEventArgs;
use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\StorageInterface;

class MediaRemoveListener
{
    private LoggerInterface $logger;
    private StorageInterface $storage;

    public function __construct(LoggerInterface $logger, StorageInterface $storage)
    {
        $this->logger = $logger;
        $this->storage = $storage;
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
