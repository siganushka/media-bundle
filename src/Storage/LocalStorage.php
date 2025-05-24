<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\UrlHelper;

class LocalStorage implements StorageInterface
{
    public function __construct(private readonly UrlHelper $urlHelper, private readonly string $publicDir)
    {
    }

    public function save(string|\SplFileInfo $originFile, ?string $targetFile = null): string
    {
        if (\is_string($originFile)) {
            $originFile = new File($originFile);
        }

        if (!$originFile instanceof File) {
            $originFile = new File($originFile->getPathname());
        }

        $filename = \sprintf('%s/uploads/%s', $this->publicDir, $targetFile ?? $originFile->getBasename());
        $pathinfo = pathinfo($filename);

        $targetFile = $originFile->move($pathinfo['dirname'], $pathinfo['basename']);

        $path = $targetFile->getPathname();
        if (str_starts_with($path, $this->publicDir)) {
            $path = substr($path, \strlen($this->publicDir));
        }

        return $this->urlHelper->getAbsoluteUrl($path);
    }

    public function delete(string $url): void
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($path)) {
            throw new \RuntimeException('Unable parse file.');
        }

        $file = $this->publicDir.$path;
        if (is_dir($file)) {
            // delete file only (not include directory)
            throw new \RuntimeException(\sprintf('File %s invalid.', $file));
        }

        if (is_file($file)) {
            @unlink($file);
        }
    }
}
