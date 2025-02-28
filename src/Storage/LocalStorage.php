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

    public function save(\SplFileInfo $origin, string $target): string
    {
        if (!$origin instanceof File) {
            $origin = new File($origin->getPathname());
        }

        $filename = \sprintf('%s/uploads/%s', $this->publicDir, $target);
        $pathinfo = pathinfo($filename);

        $targetFile = $origin->move($pathinfo['dirname'], $pathinfo['basename']);

        $path = $targetFile->getPathname();
        if (str_starts_with($path, $this->publicDir)) {
            $path = substr($path, \strlen($this->publicDir));
        }

        return $this->urlHelper->getAbsoluteUrl($path);
    }

    public function delete(string $url): void
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (null === $path || false === $path) {
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
