<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\MediaEvent;
use Siganushka\MediaBundle\MediaNaming;
use Siganushka\MediaBundle\Rule;

class MediaNamingTest extends TestCase
{
    public function testAll(): void
    {
        $rule = new Rule('foo');
        $file = './tests/Fixtures/php.jpg';

        $event = new MediaEvent($rule, $file);
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c', $event->getHash());
        static::assertSame('7e/0bd17a39cf13a.jpg', (new MediaNaming())->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', date('y')), (new MediaNaming('[yy].[ext]'))->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', date('Y')), (new MediaNaming('[yyyy].[ext]'))->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', date('n')), (new MediaNaming('[m].[ext]'))->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', date('m')), (new MediaNaming('[mm].[ext]'))->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', date('j')), (new MediaNaming('[d].[ext]'))->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', date('d')), (new MediaNaming('[dd].[ext]'))->getTargetFile($event));
        static::assertSame(\sprintf('%s.jpg', time()), (new MediaNaming('[timestamp].[ext]'))->getTargetFile($event));
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c.jpg', (new MediaNaming('[hash].[ext]'))->getTargetFile($event));
        static::assertSame('foo.jpg', (new MediaNaming('[rule].[ext]'))->getTargetFile($event));
        static::assertSame('php.jpg', (new MediaNaming('[original_name]'))->getTargetFile($event));
    }
}
