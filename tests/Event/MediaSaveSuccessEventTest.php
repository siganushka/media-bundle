<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveSuccessEvent;
use Symfony\Component\HttpFoundation\Response;

class MediaSaveSuccessEventTest extends TestCase
{
    public function testAll(): void
    {
        $chnnel = new Channel('foo');
        $file = new \SplFileInfo('./tests/Fixtures/php.jpg');

        $event = new MediaSaveSuccessEvent($chnnel, $file, new Media());
        static::assertNull($event->getResponse());

        $event->setResponse(new Response('hello'));
        static::assertInstanceOf(Response::class, $event->getResponse());
    }
}
