<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(priority: -256)]
class MediaClearListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $channel = $event->getChannel();
        $file = $event->getFile();

        $this->logger->info(\sprintf('Clear processed media file "%s" in channel "%s".', $file->getPathname(), $channel->alias), [
            'exists' => $file->isFile(),
        ]);

        if ($file->isFile()) {
            @unlink($file->getPathname());
        }
    }
}
