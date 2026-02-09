<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Filesystem;

#[AsEventListener(priority: -128)]
class MediaClearListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $rule = $event->getRule();
        $file = $event->getFile();

        $this->logger->info(\sprintf('Clear processed media file "%s" in rule "%s".',
            $file->getPathname(),
            $rule->alias,
        ));

        if ($file->isFile()) {
            (new Filesystem())->remove($file->getPathname());
        }
    }
}
