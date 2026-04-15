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
        static::assertNull($event->getMedia());

        $event->setMedia($media = new Media());
        static::assertSame($media, $event->getMedia());
    }
}
