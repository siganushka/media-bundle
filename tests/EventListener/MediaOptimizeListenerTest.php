<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\EventListener\MediaOptimizeListener;
use Siganushka\MediaBundle\Rule;

class MediaOptimizeListenerTest extends TestCase
{
    public function testSkipWhenOptimizeToQualityIsNull(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::never())->method('error');

        (new MediaOptimizeListener($logger))(new MediaSaveEvent(new Rule('foo'), './tests/Fixtures/php.jpg'));
    }

    public function testLogErrorWhenOptimizeFails(): void
    {
        $target = \sprintf('%s/media_optimize_%s.jpg', sys_get_temp_dir(), uniqid());
        copy('./tests/Fixtures/php.jpg', $target);

        $event = new MediaSaveEvent(new Rule('foo', optimizeToQuality: 85), $target);
        unlink($target);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('error')
            ->with('Error on optimize file.', static::isType('array'))
        ;

        (new MediaOptimizeListener($logger))($event);
    }
}
