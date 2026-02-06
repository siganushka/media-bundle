<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Rule;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class RuleTest extends TestCase
{
    public function testAll(): void
    {
        $rule = new Rule('foo');
        static::assertInstanceOf(\Stringable::class, $rule);
        static::assertSame('foo', (string) $rule);
        static::assertSame('foo', $rule->alias);
        static::assertSame(File::class, $rule->constraint);
        static::assertSame([], $rule->constraintOptions);
        static::assertNull($rule->namingStrategy);
        static::assertNull($rule->resizeToMaxWidth);
        static::assertNull($rule->resizeToMaxHeight);
        static::assertNull($rule->optimizeToQuality);
    }

    public function testCustomArguments(): void
    {
        $rule = new Rule('bar', Image::class, ['maxSize' => '8M', 'mimeTypes' => ['image/*']], '[original_name]', 50, 100, 85);
        static::assertInstanceOf(\Stringable::class, $rule);
        static::assertSame('bar', (string) $rule);
        static::assertSame('bar', $rule->alias);
        static::assertSame(Image::class, $rule->constraint);
        static::assertSame('[original_name]', $rule->namingStrategy);
        static::assertSame(50, $rule->resizeToMaxWidth);
        static::assertSame(100, $rule->resizeToMaxHeight);
        static::assertSame(85, $rule->optimizeToQuality);

        $constraint = $rule->getConstraint();
        static::assertInstanceOf(Image::class, $constraint);
        static::assertSame(['image/*'], $constraint->mimeTypes);
        static::assertSame(8000000, $constraint->maxSize);
    }
}
