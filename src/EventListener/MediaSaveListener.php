<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\EventListener;

use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsEventListener(priority: -128)]
class MediaSaveListener
{
    public function __construct(
        private readonly StorageInterface $storage,
        private readonly MediaRepository $repository)
    {
    }

    public function __invoke(MediaSaveEvent $event): void
    {
        $file = $event->getFile();
        if (!$file instanceof File) {
            $file = new File($file->getPathname());
        }

        // [important] Clears file status cache before access file
        clearstatcache(true, $file->getPathname());

        $normalizedName = $this->getNormalizedFileName($file);
        $extension = $file->guessExtension() ?? $file->getExtension();
        $mime = $file->getMimeType() ?? 'n/a';
        $size = $file->getSize() ?: 0;

        try {
            [$width, $height] = FileUtils::getImageSize($file);
        } catch (\Throwable) {
            $width = $height = null;
        }

        $targetFileName = \sprintf('%02s/%02s/%07s.%s',
            mb_substr($event->getHash(), 0, 2),
            mb_substr($event->getHash(), 2, 2),
            mb_substr($event->getHash(), 0, 7),
            $extension);

        $url = $this->storage->save($file, $targetFileName);

        $media = $this->repository->createNew();
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

    private function getNormalizedFileName(\SplFileInfo $file): string
    {
        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        if ($search = mb_substr(pathinfo($name, \PATHINFO_FILENAME), 32)) {
            $name = str_replace($search, '', $name);
        }

        return $name;
    }
}
