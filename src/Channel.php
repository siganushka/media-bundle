<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\File as AssertFile;

final class Channel
{
    /**
     * @param array{
     *  constraint: class-string<AssertFile>,
     *  constraint_options?: array<string, mixed>
     * } $options
     */
    public function __construct(private readonly string $alias, private readonly array $options)
    {
    }

    public function getTargetName(\SplFileInfo $file): string
    {
        $hash = md5_file($file->getPathname());
        if (false === $hash) {
            throw new \RuntimeException('Unable to hash file.');
        }

        $extension = $file->getExtension();
        if ($file instanceof File) {
            $extension = $file->guessExtension() ?? $extension;
        }

        // Like Git commit ID
        return \sprintf('%02s/%02s/%07s.%s',
            mb_substr($hash, 0, 2),
            mb_substr($hash, 2, 2),
            mb_substr($hash, 0, 7),
            $extension
        );
    }

    public function getConstraint(): AssertFile
    {
        $ref = new \ReflectionClass($this->options['constraint']);

        return $ref->newInstanceArgs($this->options['constraint_options'] ?? []);
    }

    public function __toString(): string
    {
        return $this->alias;
    }
}
