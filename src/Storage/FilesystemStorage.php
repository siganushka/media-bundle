<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\UrlHelper;

class FilesystemStorage implements StorageInterface
{
    private UrlHelper $urlHelper;
    private string $publicDir;

    public function __construct(UrlHelper $urlHelper, string $publicDir)
    {
        $this->urlHelper = $urlHelper;
        $this->publicDir = $publicDir;
    }

    public function save(ChannelInterface $channel, File $media): string
    {
        $pathname = $channel->getPathname($media);
        $filename = $channel->getFilename($media);

        $directory = sprintf('%s/%s', $this->publicDir, trim($pathname, '/'));

        try {
            $targetFile = $media->move($directory, $filename);
        } catch (\Throwable $th) {
            // add logger...
            throw $th;
        }

        $path = $targetFile->getRealPath();
        if (str_starts_with($path, $this->publicDir)) {
            $path = substr($path, \strlen($this->publicDir));
        }

        return $this->urlHelper->getAbsoluteUrl($path);
    }
}
