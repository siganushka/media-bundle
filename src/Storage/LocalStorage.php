<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\UrlHelper;

class LocalStorage extends AbstractStorage
{
    public function __construct(private readonly UrlHelper $urlHelper, private readonly string $storageDir, array $options = [])
    {
        parent::__construct($options[self::PREFIX_DIR] ?? 'uploads');
    }

    public function doSave(\SplFileInfo $originFile, string $targetFile): string
    {
        if (!$originFile instanceof File) {
            $originFile = new File($originFile->getPathname());
        }

        $filename = Path::join($this->storageDir, $targetFile);
        $pathinfo = pathinfo($filename);

        $originFile->move($pathinfo['dirname'] ?? '', $pathinfo['basename']);

        return $this->buildUrl($targetFile);
    }

    public function doDelete(string $path): void
    {
        $file = Path::join($this->storageDir, $path);
        if (is_file($file) && !is_dir($file)) {
            (new Filesystem())->remove($file);
        }
    }

    public function buildUrl(string $path): string
    {
        return $this->urlHelper->getAbsoluteUrl($path);
    }
}
