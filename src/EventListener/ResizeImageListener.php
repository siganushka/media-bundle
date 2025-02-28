<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\ResizeImageEvent;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResizeImageListener implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function onResizeImage(ResizeImageEvent $event): void
    {
        if (!class_exists(\Imagick::class)) {
            return;
        }

        $maxWidth = $event->getMaxWidth();
        $maxHeight = $event->getMaxHeight();

        $maxWidth && $this->resizeByMaxWidth($event->getFile(), $maxWidth);
        $maxHeight && $this->resizeByMaxHeight($event->getFile(), $maxHeight);
    }

    public function resizeByMaxWidth(\SplFileInfo $file, int $maxWidth): void
    {
        [$width, $height] = FileUtils::getImageSize($file);
        if ($width <= $maxWidth) {
            return;
        }

        $this->logger->info(\sprintf('Start Resizing %s by max width %d', $file->getPathname(), $maxWidth));

        $newHeight = (int) round($height * ($maxWidth / $width));

        $imagick = new \Imagick($file->getPathname());
        $imagick->setImageCompressionQuality(85);
        $imagick->thumbnailImage($maxWidth, $newHeight);
        $imagick->writeImage($file->getPathname());

        // [important] Clears file status cache
        clearstatcache(true, $file->getPathname());

        [$newWidth, $newHeight] = FileUtils::getImageSize($file);
        $this->logger->info(\sprintf('Successfully resized %s to max width (%d*%d -> %d*%d).',
            $file->getPathname(),
            $width,
            $height,
            $newWidth,
            $newHeight,
        ));
    }

    public function resizeByMaxHeight(\SplFileInfo $file, int $maxHeight): void
    {
        [$width, $height] = FileUtils::getImageSize($file);
        if ($height <= $maxHeight) {
            return;
        }

        $this->logger->info(\sprintf('Start Resizing %s by max height %d', $file->getPathname(), $maxHeight));

        $newWidth = (int) round($width * ($maxHeight / $height));

        $imagick = new \Imagick($file->getPathname());
        $imagick->setImageCompressionQuality(85);
        $imagick->thumbnailImage($newWidth, $maxHeight);
        $imagick->writeImage($file->getPathname());

        // [important] Clears file status cache
        clearstatcache(true, $file->getPathname());

        [$newWidth, $newHeight] = FileUtils::getImageSize($file);
        $this->logger->info(\sprintf('Successfully resized %s to max height (%d*%d -> %d*%d).',
            $file->getPathname(),
            $width,
            $height,
            $newWidth,
            $newHeight,
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ResizeImageEvent::class => 'onResizeImage',
        ];
    }
}
