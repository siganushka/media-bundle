<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\ResizeImageEvent;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResizeImageListener implements EventSubscriberInterface
{
    public function onResizeImage(ResizeImageEvent $event): void
    {
        if (!class_exists(\Imagick::class)) {
            return;
        }

        $file = $event->getFile();
        if (null !== $event->getMaxWidth()) {
            $this->resizeMaxWidth($file, $event->getMaxWidth());
        }

        if (null !== $event->getMaxHeight()) {
            $this->resizeMaxHeight($file, $event->getMaxHeight());
        }
    }

    private function resizeMaxWidth(\SplFileInfo $file, int $maxWidth): void
    {
        [$width, $height] = FileUtils::getImageSize($file);
        if ($width <= $maxWidth) {
            return;
        }

        $newHeight = (int) round($height * ($maxWidth / $width));

        $imagick = new \Imagick($file->getPathname());
        $imagick->setImageCompressionQuality(70);
        $imagick->thumbnailImage($maxWidth, $newHeight);
        $imagick->writeImage($file->getPathname());

        // [important] Clears file status cache
        clearstatcache(true, $file->getPathname());
    }

    private function resizeMaxHeight(\SplFileInfo $file, int $maxHeight): void
    {
        [$width, $height] = FileUtils::getImageSize($file);
        if ($width <= $maxHeight) {
            return;
        }

        $newWidth = (int) round($width * ($maxHeight / $height));

        $imagick = new \Imagick($file->getPathname());
        $imagick->setImageCompressionQuality(70);
        $imagick->thumbnailImage($newWidth, $maxHeight);
        $imagick->writeImage($file->getPathname());

        // [important] Clears file status cache
        clearstatcache(true, $file->getPathname());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResizeImageEvent::class => 'onResizeImage',
        ];
    }
}
