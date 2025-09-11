<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Rule;

class MediaSaveEventTest extends TestCase
{
    public function testAll(): void
    {
        $rule = new Rule('foo');
        $file = new \SplFileInfo('./tests/Fixtures/php.jpg');

        $event = new MediaSaveEvent($rule, $file);

        // Assert lazy hash file.
        $rp = new \ReflectionProperty(MediaSaveEvent::class, 'hash');
        static::assertNull($rp->getValue($event));

        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c', $event->getHash());
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c', $rp->getValue($event));

        static::assertNull($event->getMedia());
        static::assertSame($rule, $event->getRule());
        static::assertSame($file, $event->getFile());

        $event->setMedia(new Media());
        static::assertInstanceOf(Media::class, $event->getMedia());
    }
}
