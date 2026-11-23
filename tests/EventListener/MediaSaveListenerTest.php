<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\EventListener\MediaSaveListener;
use Siganushka\MediaBundle\NamingStrategy;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaSaveListenerTest extends TestCase
{
    public function testSave(): void
    {
        $target = $this->copyFixture('landscape.jpg');
        $event = new MediaSaveEvent(new Rule('foo'), new \SplFileInfo($target));
        $media = new TestMedia();

        $listener = $this->createListener($media);
        $listener($event);

        static::assertSame($media, $event->getMedia());
        static::assertSame($event->getHash(), $media->getHash());
        static::assertSame('landscape.jpg', $media->getName());
        static::assertSame('jpg', $media->getExtension());
        static::assertSame('image/jpeg', $media->getMime());
        static::assertSame(filesize($target), $media->getBytes());
        static::assertSame(500, $media->getWidth());
        static::assertSame(300, $media->getHeight());
        static::assertSame('http://localhost/foo/bar.jpg', $media->getUrl());
    }

    public function testSaveUploadedFile(): void
    {
        $target = $this->copyFixture('landscape.jpg');
        $event = new MediaSaveEvent(new Rule('foo'), new UploadedFile($target, 'My Photo.jpg', 'image/jpeg', null, true));
        $media = new TestMedia();

        $this->createListener($media)($event);

        static::assertSame('My-Photo.jpg', $media->getName());
    }

    public function testSaveFileWithoutImageSize(): void
    {
        $target = \sprintf('%s/media_save_%s.txt', sys_get_temp_dir(), uniqid());
        file_put_contents($target, 'hello');

        $event = new MediaSaveEvent(new Rule('foo'), new \SplFileInfo($target));
        $media = new TestMedia();

        $this->createListener($media)($event);

        static::assertSame('text/plain', $media->getMime());
        static::assertSame(5, $media->getBytes());
        static::assertNull($media->getWidth());
        static::assertNull($media->getHeight());

        unlink($target);
    }

    public function testReuseExistingMedia(): void
    {
        $target = $this->copyFixture('landscape.jpg');
        $event = new MediaSaveEvent(new Rule('foo'), new \SplFileInfo($target));
        $media = new TestMedia();
        $event->setMedia($media);

        $repository = $this->createMock(MediaRepository::class);
        $repository->expects(static::never())->method('createNew');

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('save')->willReturn('http://localhost/foo/bar.jpg');

        $naming = $this->createMock(NamingStrategy::class);
        $naming->method('getTargetFile')->willReturn('foo/bar.jpg');

        (new MediaSaveListener($storage, $repository, $naming))($event);

        static::assertSame($media, $event->getMedia());
        static::assertSame('landscape.jpg', $media->getName());
    }

    private function createListener(TestMedia $media): MediaSaveListener
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(static::once())
            ->method('save')
            ->with(static::isInstanceOf(\SplFileInfo::class), 'foo/bar.jpg')
            ->willReturn('http://localhost/foo/bar.jpg')
        ;

        $repository = $this->createMock(MediaRepository::class);
        $repository->method('createNew')->willReturn($media);

        $naming = $this->createMock(NamingStrategy::class);
        $naming->method('getTargetFile')->willReturn('foo/bar.jpg');

        return new MediaSaveListener($storage, $repository, $naming);
    }

    private function copyFixture(string $fixture): string
    {
        $dir = \sprintf('%s/media_save_%s', sys_get_temp_dir(), uniqid());
        mkdir($dir, 0o777, true);

        $target = \sprintf('%s/%s', $dir, $fixture);
        copy('./tests/Fixtures/'.$fixture, $target);

        return $target;
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(\sprintf('%s/media_save_*', sys_get_temp_dir()));
    }
}
