<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\MediaEvent;
use Siganushka\MediaBundle\Rule;
use Symfony\Component\HttpFoundation\File\File;

class MediaEventTest extends TestCase
{
    public function testAll(): void
    {
        $rule = new Rule('foo');
        $file = new \SplFileInfo('./tests/Fixtures/php.jpg');

        $event = new MediaEvent($rule, $file);
        static::assertSame('foo', $event->getRule()->__toString());
        static::assertSame('./tests/Fixtures/php.jpg', $event->getFile()->getPathname());
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c', $event->getHash());
    }

    public function testGetSymfonyFile(): void
    {
        $file = new File('./tests/Fixtures/php.jpg');

        static::assertSame($file, MediaEvent::getSymfonyFile($file));
        static::assertInstanceOf(File::class, MediaEvent::getSymfonyFile('./tests/Fixtures/php.jpg'));
        static::assertSame('./tests/Fixtures/php.jpg', MediaEvent::getSymfonyFile(new \SplFileInfo('./tests/Fixtures/php.jpg'))->getPathname());
    }

    public function testHashDependsOnRule(): void
    {
        $file = './tests/Fixtures/php.jpg';

        static::assertNotSame(
            (new MediaEvent(new Rule('foo'), $file))->getHash(),
            (new MediaEvent(new Rule('bar'), $file))->getHash()
        );
    }
}
