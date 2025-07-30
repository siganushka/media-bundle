<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(priority: 128)]
class MediaCheckListener
{
    public function __construct(private readonly MediaRepository $repository)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $media = $this->repository->findOneByHash($event->getHash());
        if ($media) {
            $event->setMedia($media)->stopPropagation();
        }
    }
}
