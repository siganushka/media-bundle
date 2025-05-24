<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

interface StorageInterface
{
    /**
     * Save the file to the storage system.
     *
     * @param string|\SplFileInfo $originFile Origin file object
     * @param string|null         $targetFile The file name saved to the storage system
     *
     * @return string File URL
     */
    public function save(string|\SplFileInfo $originFile, ?string $targetFile = null): string;

    /**
     * Delete files from the storage by file URL.
     *
     * @param string $url File URL
     *
     * @throws \RuntimeException Thrown when deletion fails
     */
    public function delete(string $url): void;
}
