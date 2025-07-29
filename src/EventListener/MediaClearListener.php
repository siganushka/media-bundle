<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaSaveSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class MediaClearListener
{
    public function __invoke(MediaSaveSuccessEvent $event): void
    {
        $file = $event->getFile();
        if ($file->isFile()) {
            @unlink($file->getPathname());
        }
    }
}
