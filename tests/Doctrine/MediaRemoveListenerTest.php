<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Doctrine;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Doctrine\MediaRemoveListener;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;

class MediaRemoveListenerTest extends TestCase
{
    public function testIgnoreMediaWithoutUrl(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(static::never())->method('delete');

        (new MediaRemoveListener($storage))(new TestMedia());
    }

    public function testDeleteMediaFile(): void
    {
        $media = new TestMedia();
        $media->setUrl('http://localhost/foo/bar.jpg');

        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(static::once())
            ->method('delete')
            ->with('http://localhost/foo/bar.jpg')
        ;

        (new MediaRemoveListener($storage))($media);
    }

    public function testIgnoreStorageException(): void
    {
        $media = new TestMedia();
        $media->setUrl('http://localhost/foo/bar.jpg');

        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(static::once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('Unable to delete file.'))
        ;

        (new MediaRemoveListener($storage))($media);

        static::assertSame('http://localhost/foo/bar.jpg', $media->getUrl());
    }
}
