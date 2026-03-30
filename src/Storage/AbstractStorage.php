<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Symfony\Component\Filesystem\Path;

abstract class AbstractStorage implements StorageInterface
{
    public const PREFIX_DIR = 'prefix_dir';

    public function __construct(private readonly ?string $prefixDir = null)
    {
    }

    public function save(string|\SplFileInfo $originFile, ?string $targetFile = null): string
    {
        $targetFile ??= \is_string($originFile) ? pathinfo($originFile, \PATHINFO_BASENAME) : $originFile->getBasename();
        $targetFileName = Path::join('/', $this->prefixDir ?? '', $targetFile);

        return $this->doSave($originFile, $targetFileName);
    }

    public function delete(string $url): void
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($path)) {
            throw new \RuntimeException('Unable parse file.');
        }

        $this->doDelete($path);
    }

    abstract public function doSave(string|\SplFileInfo $originFile, string $targetFile): string;

    abstract public function doDelete(string $path): void;
}
