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
        $newFile = sprintf('%s/%s', $this->publicDir, ltrim($channel->getNewFile($media), '/'));

        $directory = pathinfo($newFile, \PATHINFO_DIRNAME);
        $name = pathinfo($newFile, \PATHINFO_BASENAME);

        try {
            $media->move($directory, $name);
        } catch (\Throwable $th) {
            // write logger...
            throw $th;
        }

        if (str_starts_with($newFile, $this->publicDir)) {
            $newFile = substr($newFile, \strlen($this->publicDir));
        }

        return $this->urlHelper->getAbsoluteUrl($newFile);
    }
}
