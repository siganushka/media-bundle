<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\UrlHelper;

class LocalStorage extends AbstractStorage
{
    public function __construct(private readonly UrlHelper $urlHelper, private readonly string $publicDir, array $options = [])
    {
        parent::__construct($options[self::PREFIX_DIR] ?? 'uploads');
    }

    public function doSave(\SplFileInfo $originFile, string $targetFileToSave): string
    {
        if (!$originFile instanceof File) {
            $originFile = new File($originFile->getPathname());
        }

        $filename = $this->publicDir.'/'.$targetFileToSave;
        $pathinfo = pathinfo($filename);

        $originFile->move($pathinfo['dirname'], $pathinfo['basename']);

        return $this->urlHelper->getAbsoluteUrl('/'.$targetFileToSave);
    }

    public function doDelete(string $path): void
    {
        $file = $this->publicDir.$path;
        if (is_file($file) && !is_dir($file)) {
            @unlink($file);
        }
    }
}
