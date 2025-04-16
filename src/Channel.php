<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\File as AssertFile;

final class Channel
{
    public function __construct(
        public readonly string $alias,
        /** @var class-string<AssertFile> */
        public readonly string $constraint = AssertFile::class,
        public readonly array $constraintOptions = [],
        public readonly ?int $maxWidth = null,
        public readonly ?int $maxHeight = null,
        public readonly ?int $optimize = null)
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
        $ref = new \ReflectionClass($this->constraint);

        return $ref->newInstanceArgs($this->constraintOptions);
    }

    public function __toString(): string
    {
        return $this->alias;
    }
}
