<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\EventListener\MediaClearListener;
use Siganushka\MediaBundle\Rule;
use Symfony\Component\Filesystem\Filesystem;

class MediaClearListenerTest extends TestCase
{
    public function testRemoveProcessedFile(): void
    {
        $target = \sprintf('%s/media_clear_%s.jpg', sys_get_temp_dir(), uniqid());
        copy('./tests/Fixtures/php.jpg', $target);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('info')
            ->with(static::stringContains('Clear processed media file'))
        ;

        (new MediaClearListener($logger))(new MediaSaveEvent(new Rule('foo'), $target));

        static::assertFileDoesNotExist($target);
    }

    public function testIgnoreMissingFile(): void
    {
        $target = \sprintf('%s/media_clear_missing_%s.jpg', sys_get_temp_dir(), uniqid());
        copy('./tests/Fixtures/php.jpg', $target);

        $event = new MediaSaveEvent(new Rule('foo'), $target);
        unlink($target);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())
            ->method('info')
            ->with(static::stringContains('Clear processed media file'))
        ;

        (new MediaClearListener($logger))($event);

        static::assertFileDoesNotExist($target);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(\sprintf('%s/media_clear_*', sys_get_temp_dir()));
    }
}
