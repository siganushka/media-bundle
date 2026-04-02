<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Event\MediaEvent;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NamingStrategy
{
    public const DEFAULT_NAMING = '[hash:2]/[hash:13:2].[ext]';

    public function __construct(
        private readonly RuleRegistry $ruleRegistry,
        private readonly string $defaultNamingStrategy = self::DEFAULT_NAMING,
        private readonly array $defaultPlaceholders = [],
    ) {
    }

    public function getTargetFile(string|Rule $rule, string|\SplFileInfo $file): string
    {
        if (\is_string($rule)) {
            $rule = $this->ruleRegistry->get($rule);
        }

        $event = new MediaEvent($rule, $file);
        $file = $event->getFile();

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $normalizedName = FileUtils::normalizeFilename($name);
        $extension = $file->guessExtension() ?? $file->getExtension();

        $callback = static fn (array $matches) => mb_substr($event->getHash(), (int) ($matches[2] ?? 0), (int) $matches[1]);
        $namingStrategy = $rule->namingStrategy ?? $this->defaultNamingStrategy;

        if ($naming = preg_replace_callback('/\[hash:(\d+)(?::(\d+))?\]/', $callback, $namingStrategy)) {
            return strtr($naming, $this->defaultPlaceholders + [
                '[yy]' => date('y'),
                '[yyyy]' => date('Y'),
                '[m]' => date('n'),
                '[mm]' => date('m'),
                '[d]' => date('j'),
                '[dd]' => date('d'),
                '[timestamp]' => time(),
                '[uniqid]' => uniqid(),
                '[hash]' => $event->getHash(),
                '[rule]' => $rule->__toString(),
                '[ext]' => $extension,
                '[original_name_with_ext]' => $normalizedName,
            ]);
        }

        return $normalizedName;
    }
}
