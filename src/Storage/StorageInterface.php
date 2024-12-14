<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

interface StorageInterface
{
    public function save(\SplFileInfo $origin, string $target): string;

    public function delete(string $url): void;
}
