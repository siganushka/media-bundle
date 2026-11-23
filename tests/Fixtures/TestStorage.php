<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Fixtures;

use Siganushka\MediaBundle\Storage\AbstractStorage;

class TestStorage extends AbstractStorage
{
    /** @var array<int, array{string|\SplFileInfo, string}> */
    public array $saved = [];

    /** @var array<int, string> */
    public array $deleted = [];

    public function doSave(string|\SplFileInfo $originFile, string $targetFile): string
    {
        $this->saved[] = [$originFile, $targetFile];

        return $targetFile;
    }

    public function doDelete(string $path): void
    {
        $this->deleted[] = $path;
    }

    public function buildUrl(string $path): string
    {
        return 'http://localhost/'.ltrim($path, '/');
    }
}
