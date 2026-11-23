<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\EventListener\MediaCheckListener;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;

class MediaCheckListenerTest extends TestCase
{
    public function testStopPropagationWhenMediaExists(): void
    {
        $event = new MediaSaveEvent(new Rule('foo'), './tests/Fixtures/php.jpg');
        $media = new TestMedia();

        $repository = $this->createMock(MediaRepository::class);
        $repository->expects(static::once())
            ->method('findOneByHash')
            ->with($event->getHash())
            ->willReturn($media)
        ;

        (new MediaCheckListener($repository))($event);

        static::assertSame($media, $event->getMedia());
        static::assertTrue($event->isPropagationStopped());
    }

    public function testContinuePropagationWhenMediaNotExists(): void
    {
        $event = new MediaSaveEvent(new Rule('foo'), './tests/Fixtures/php.jpg');

        $repository = $this->createMock(MediaRepository::class);
        $repository->method('findOneByHash')
            ->willReturn(null)
        ;

        (new MediaCheckListener($repository))($event);

        static::assertNull($event->getMedia());
        static::assertFalse($event->isPropagationStopped());
    }
}
