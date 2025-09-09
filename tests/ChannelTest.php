<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Channel;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ChannelTest extends TestCase
{
    public function testAll(): void
    {
        $channel = new Channel('foo');
        static::assertInstanceOf(\Stringable::class, $channel);
        static::assertSame('foo', (string) $channel);
        static::assertSame('foo', $channel->alias);
        static::assertSame(File::class, $channel->constraint);
        static::assertSame([], $channel->constraintOptions);
        static::assertFalse($channel->reserveClientName);
        static::assertNull($channel->resizeToMaxWidth);
        static::assertNull($channel->resizeToMaxHeight);
        static::assertNull($channel->optimizeToQuality);
    }

    public function testCustomArguments(): void
    {
        $channel = new Channel('bar', Image::class, ['maxSize' => '8M', 'mimeTypes' => ['image/*']], true, 50, 100, 85);
        static::assertInstanceOf(\Stringable::class, $channel);
        static::assertSame('bar', (string) $channel);
        static::assertSame('bar', $channel->alias);
        static::assertSame(Image::class, $channel->constraint);
        static::assertTrue($channel->reserveClientName);
        static::assertSame(50, $channel->resizeToMaxWidth);
        static::assertSame(100, $channel->resizeToMaxHeight);
        static::assertSame(85, $channel->optimizeToQuality);

        $constraint = $channel->getConstraint();
        static::assertInstanceOf(Image::class, $constraint);
        static::assertSame(['image/*'], $constraint->mimeTypes);
        static::assertSame(8000000, $constraint->maxSize);
    }
}
