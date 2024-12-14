<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\ResizeImageEvent;
use Siganushka\MediaBundle\EventListener\ResizeImageListener;
use Siganushka\MediaBundle\Utils\FileUtils;

class ResizeImageListenerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Imagick::class)) {
            static::markTestSkipped('Skip tests (Imagick not loaded).');
        }
    }

    public function testResizeImageMaxWidth(): void
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

        $listener = new ResizeImageListener();
        $listener->onResizeImage(new ResizeImageEvent($file, 500));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(500, $width);
        static::assertSame(300, $height);
        static::assertSame($size, $file->getSize());

        $listener = new ResizeImageListener();
        $listener->onResizeImage(new ResizeImageEvent($file, 250));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(250, $width);
        static::assertSame(150, $height);
        static::assertNotSame($size, $file->getSize());

        $listener = new ResizeImageListener();
        $listener->onResizeImage(new ResizeImageEvent($file, 50));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(50, $width);
        static::assertSame(30, $height);
        static::assertNotSame($size, $file->getSize());

        unlink($target);
    }

    public function testResizeImageMaxHeight(): void
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

        $listener = new ResizeImageListener();
        $listener->onResizeImage(new ResizeImageEvent($file, null, 500));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(300, $width);
        static::assertSame(500, $height);
        static::assertSame($size, $file->getSize());

        $listener = new ResizeImageListener();
        $listener->onResizeImage(new ResizeImageEvent($file, null, 250));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(150, $width);
        static::assertSame(250, $height);
        static::assertNotSame($size, $file->getSize());

        $listener = new ResizeImageListener();
        $listener->onResizeImage(new ResizeImageEvent($file, null, 50));

        [$width, $height] = FileUtils::getImageSize($file);
        static::assertSame(30, $width);
        static::assertSame(50, $height);
        static::assertNotSame($size, $file->getSize());

        unlink($target);
    }
}
