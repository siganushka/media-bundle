<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MediaManager implements MediaManagerInterface
{
    public function __construct(
        private readonly ChannelRegistry $channelRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function save(string|Channel $channel, string|\SplFileInfo $file): Media
    {
        if (\is_string($channel)) {
            $channel = $this->channelRegistry->get($channel);
        }

        if (\is_string($file)) {
            $file = new File($file);
        }

        $event = new MediaSaveEvent($channel, $file);
        $this->eventDispatcher->dispatch($event);

        return $event->getMedia() ?? throw new \RuntimeException('Unable to save file.');
    }
}
