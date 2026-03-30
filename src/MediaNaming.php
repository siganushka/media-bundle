<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Event\MediaEvent;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaNaming
{
    public const DEFAULT_NAMING = '[hash:2]/[hash:13:2].[ext]';

    public function __construct(private readonly string $defaultNamingStrategy = self::DEFAULT_NAMING)
    {
    }

    public function getTargetFile(MediaEvent $event): string
    {
        $file = $event->getFile();

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $normalizedName = FileUtils::normalizeFilename($name);
        $extension = $file->guessExtension() ?? $file->getExtension();

        $callback = static fn (array $matches) => mb_substr($event->getHash(), (int) ($matches[2] ?? 0), (int) $matches[1]);
        $namingStrategy = $event->getRule()->namingStrategy ?? $this->defaultNamingStrategy;

        if ($naming = preg_replace_callback('/\[hash:(\d+)(?::(\d+))?\]/', $callback, $namingStrategy)) {
            return strtr($naming, [
                '[yy]' => date('y'),
                '[yyyy]' => date('Y'),
                '[m]' => date('n'),
                '[mm]' => date('m'),
                '[d]' => date('j'),
                '[dd]' => date('d'),
                '[timestamp]' => time(),
                '[hash]' => $event->getHash(),
                '[rule]' => $event->getRule(),
                '[original_name]' => $normalizedName,
                '[ext]' => $extension,
            ]);
        }

        return $normalizedName;
    }
}
