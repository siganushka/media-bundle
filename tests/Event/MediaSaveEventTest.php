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
        static::assertSame('foo', $event->getRule()->__toString());
        static::assertSame('./tests/Fixtures/php.jpg', $event->getFile()->getPathname());
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c', $event->getHash());
        static::assertNull($event->getMedia());

        $event->setMedia($media = new Media());
        static::assertSame($media, $event->getMedia());
    }
}
