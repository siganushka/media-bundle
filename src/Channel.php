<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\Validator\Constraints\File as AssertFile;

final class Channel implements \Stringable
{
    public function __construct(
        public readonly string $alias,
        /** @var class-string<AssertFile> */
        public readonly string $constraint = AssertFile::class,
        public readonly array $constraintOptions = [],
        public readonly ?int $resizeToMaxWidth = null,
        public readonly ?int $resizeToMaxHeight = null,
        public readonly ?int $optimizeToQuality = null)
    {
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
