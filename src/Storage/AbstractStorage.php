<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;

abstract class AbstractStorage implements StorageInterface
{
    public function __construct(private readonly ?string $prefixDir = null)
    {
    }

    public function save(string|\SplFileInfo $originFile, ?string $targetFile = null): string
    {
        if (\is_string($originFile)) {
            $originFile = new File($originFile);
        }

        $targetFileToSave = $targetFile ?? $originFile->getBasename();
        if ($this->prefixDir) {
            $targetFileToSave = $this->prefixDir.'/'.$targetFileToSave;
        }

        return $this->doSave($originFile, $targetFileToSave);
    }

    public function delete(string $url): void
    {
        $path = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($path)) {
            throw new \RuntimeException('Unable parse file.');
        }

        $this->doDelete($path);
    }

    abstract public function doSave(\SplFileInfo $originFile, string $targetFileToSave): string;

    abstract public function doDelete(string $path): void;
}
