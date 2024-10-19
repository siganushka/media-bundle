<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\StorageInterface;

class MediaRemoveListener
{
    public function __construct(private readonly LoggerInterface $logger, private readonly StorageInterface $storage)
    {
    }

    public function postRemove(Media $entity): void
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
