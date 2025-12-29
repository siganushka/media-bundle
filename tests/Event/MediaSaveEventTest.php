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
        static::assertSame($rule, $event->getRule());
        static::assertSame($file, $event->getFile());
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c', $event->getHash());
        static::assertNull($event->getMedia());

        $event->setMedia($media = new Media());
        static::assertSame($media, $event->getMedia());
    }

    public function testHashException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to hash file.');

        $rule = new Rule('foo');
        $file = new \SplFileInfo('./non_exists_file.jpg');

        $event = new MediaSaveEvent($rule, $file);
        $event->getHash();
    }
}
