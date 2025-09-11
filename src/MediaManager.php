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
        private readonly RuleRegistry $ruleRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function save(string|Rule $rule, string|\SplFileInfo $file): Media
    {
        if (\is_string($rule)) {
            $rule = $this->ruleRegistry->get($rule);
        }

        if (\is_string($file)) {
            $file = new File($file);
        }

        $event = new MediaSaveEvent($rule, $file);
        $this->eventDispatcher->dispatch($event);

        return $event->getMedia() ?? throw new \RuntimeException('Unable to save file.');
    }
}
