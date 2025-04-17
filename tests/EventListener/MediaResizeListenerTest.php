<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\EventListener\MediaResizeListener;
use Siganushka\MediaBundle\Utils\FileUtils;

class MediaResizeListenerTest extends TestCase
{
    private MediaResizeListener $listener;

    protected function setUp(): void
    {
        if (!class_exists(\Imagick::class)) {
            static::markTestSkipped('Skip tests (Imagick not loaded).');
        }

        $this->listener = new MediaResizeListener(new NullLogger());
    }

    public function testResizeImageresizeToMaxWidth(): void
    {
        $origin = './tests/Fixtures/landscape.jpg';
        $target = \sprintf('%s/%s', sys_get_temp_dir(), pathinfo($origin, \PATHINFO_BASENAME));

        if (!copy($origin, $target)) {
            static::markTestSkipped('Skip tests (Fail to copy file).');
        }

        $file = new \SplFileInfo($target);
        $size = $file->getSize();

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(500, $width);
        static::assertSame(300, $height);

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo'), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(500, $width);
        static::assertSame(300, $height);
        static::assertSame($size, $file->getSize());

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo', resizeToMaxWidth: 500), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(500, $width);
        static::assertSame(300, $height);
        static::assertSame($size, $file->getSize());

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo', resizeToMaxWidth: 250), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(250, $width);
        static::assertSame(150, $height);
        static::assertNotSame($size, $file->getSize());

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo', resizeToMaxWidth: 50), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(50, $width);
        static::assertSame(30, $height);
        static::assertNotSame($size, $file->getSize());

        unlink($target);
    }

    public function testResizeImageresizeToMaxHeight(): void
    {
        $origin = './tests/Fixtures/portrait.jpg';
        $target = \sprintf('%s/%s', sys_get_temp_dir(), pathinfo($origin, \PATHINFO_BASENAME));

        if (!copy($origin, $target)) {
            static::markTestSkipped('Skip tests (Fail to copy file).');
        }

        $file = new \SplFileInfo($target);
        $size = $file->getSize();

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(300, $width);
        static::assertSame(500, $height);

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo'), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(300, $width);
        static::assertSame(500, $height);
        static::assertSame($size, $file->getSize());

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo', resizeToMaxHeight: 500), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(300, $width);
        static::assertSame(500, $height);
        static::assertSame($size, $file->getSize());

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo', resizeToMaxHeight: 250), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(150, $width);
        static::assertSame(250, $height);
        static::assertNotSame($size, $file->getSize());

        $this->listener->onMediaSave(new MediaSaveEvent(new Channel('foo', resizeToMaxHeight: 50), $file));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(30, $width);
        static::assertSame(50, $height);
        static::assertNotSame($size, $file->getSize());

        unlink($target);
    }
}
