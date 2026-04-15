<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Event\MediaEvent;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NamingStrategy
{
    public const DEFAULT_NAMING = '[random:2]/[random:2:2]/[random:12:4].[ext]';

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

        $file = MediaEvent::getSymfonyFile($file);

        $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : $file->getFilename();
        $normalizedName = FileUtils::normalizeFilename($name);
        $extension = $file->guessExtension() ?? $file->getExtension();

        $random = $this->defaultPlaceholders['[random]'] ?? bin2hex(random_bytes(16));
        $callback = static fn (array $matches) => substr($random, (int) ($matches[2] ?? 0), (int) $matches[1]);
        $namingStrategy = $rule->namingStrategy ?? $this->defaultNamingStrategy;

        if ($naming = preg_replace_callback('/\[random:(\d+)(?::(\d+))?\]/', $callback, $namingStrategy)) {
            return strtr($naming, $this->defaultPlaceholders + [
                '[yy]' => date('y'),
                '[yyyy]' => date('Y'),
                '[m]' => date('n'),
                '[mm]' => date('m'),
                '[d]' => date('j'),
                '[dd]' => date('d'),
                '[timestamp]' => time(),
                '[uniqid]' => uniqid(),
                '[random]' => $random,
                '[rule]' => $rule->alias,
                '[ext]' => $extension,
                '[original_name_with_ext]' => $normalizedName,
            ]);
        }

        return $normalizedName;
    }
}
