<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(priority: 16)]
class MediaResizeListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        if (!class_exists(\Imagick::class)) {
            return;
        }

        $rule = $event->getRule();
        $file = $event->getFile();

        try {
            $rule->resizeToMaxWidth && $this->resizeByMaxWidth($file, $rule->resizeToMaxWidth);
        } catch (\Throwable $th) {
            $this->logger->error('Error on resize file by max width.', [
                'msg' => $th->getMessage(),
            ]);
        }

        try {
            $rule->resizeToMaxHeight && $this->resizeByMaxHeight($file, $rule->resizeToMaxHeight);
        } catch (\Throwable $th) {
            $this->logger->error('Error on resize file by max height.', [
                'msg' => $th->getMessage(),
            ]);
        }
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
}
