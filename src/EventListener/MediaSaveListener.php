<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\File;

#[AsEventListener(priority: -8)]
class MediaSaveListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MediaRepository $repository,
        private readonly string $defaultNamingStrategy,
    ) {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $file = $event->getFile();
        if (!$file instanceof File) {
            $file = new File($file->getPathname());
        }

        // [important] Clears file status cache before access file.
        clearstatcache(true, $file->getPathname());

        $normalizedName = FileUtils::getNormalizedName($file);
        $extension = $file->guessExtension() ?? throw new \RuntimeException('Unable to guess extension.');
        $mime = $file->getMimeType() ?? throw new \RuntimeException('Unable to get mime type.');
        $size = $file->getSize() ?: 0;

        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable) {
            $width = $height = null;
        }

        $namingStrategy = $event->getRule()->namingStrategy ?? $this->defaultNamingStrategy;
        $naming = preg_replace_callback('/\[hash:(\d+)(?::(\d+))?\]/', static fn (array $matches) => mb_substr($event->getHash(), (int) ($matches[2] ?? 0), (int) $matches[1]), $namingStrategy);

        $targetFile = strtr($naming ?? $normalizedName, [
            '[yy]' => date('y'),
            '[yyyy]' => date('Y'),
            '[m]' => date('n'),
            '[mm]' => date('m'),
            '[d]' => date('j'),
            '[dd]' => date('d'),
            '[timestamp]' => time(),
            '[hash]' => $event->getHash(),
            '[rule]' => $event->getRule()->__toString(),
            '[original_name]' => $normalizedName,
            '[ext]' => $extension,
        ]);

        $url = $this->storage->save($file, $targetFile);

        $media = $event->getMedia() ?? $this->repository->createNew();
        $media->setHash($event->getHash());
        $media->setName($normalizedName);
        $media->setExtension($extension);
        $media->setMime($mime);
        $media->setSize($size);
        $media->setWidth($width);
        $media->setHeight($height);
        $media->setUrl($url);

        $event->setMedia($media);
    }
}
