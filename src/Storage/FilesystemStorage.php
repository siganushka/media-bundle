<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\UrlHelper;

class FilesystemStorage implements StorageInterface
{
    private UrlHelper $urlHelper;
    private string $publicDir;
    private string $uploadDir;

    public function __construct(UrlHelper $urlHelper, string $publicDir, string $uploadDir)
    {
        $this->urlHelper = $urlHelper;
        $this->publicDir = $publicDir;
        $this->uploadDir = $uploadDir;
    }

    public function save(ChannelInterface $channel, File $file): string
    {
        $filename = sprintf('%s/%s/%s', $this->publicDir, $this->uploadDir, $channel->getTargetName($file));
        $pathinfo = pathinfo($filename);

        try {
            $targetFile = $file->move($pathinfo['dirname'], $pathinfo['basename']);
        } catch (\Throwable $th) {
            // add logger...
            throw $th;
        }

        $path = $targetFile->getPathname();
        if (str_starts_with($path, $this->publicDir)) {
            $path = substr($path, \strlen($this->publicDir));
        }

        return $this->urlHelper->getAbsoluteUrl($path);
    }

    public function delete(string $url): void
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!$path) {
            throw new \RuntimeException('Unable parse file.');
        }

        $file = sprintf('%s%s', $this->publicDir, $path);
        if (is_dir($file)) {
            // delete file only (not include directory)
            throw new \RuntimeException(sprintf('File %s invalid.', $file));
        }

        $fs = new Filesystem();
        $fs->remove($file);
    }
}
