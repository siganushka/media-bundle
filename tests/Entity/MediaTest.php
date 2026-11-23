<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;

class MediaTest extends TestCase
{
    public function testAll(): void
    {
        $media = new TestMedia();
        static::assertNull($media->getId());
        static::assertNull($media->getHash());
        static::assertNull($media->getUrl());
        static::assertNull($media->getName());
        static::assertNull($media->getExtension());
        static::assertNull($media->getMime());
        static::assertNull($media->getBytes());
        static::assertNull($media->getSize());
        static::assertNull($media->getWidth());
        static::assertNull($media->getHeight());

        $media
            ->setHash('test_hash')
            ->setUrl('http://localhost/foo/bar.jpg')
            ->setName('bar.jpg')
            ->setExtension('jpg')
            ->setMime('image/jpeg')
            ->setBytes(1536)
            ->setWidth(300)
            ->setHeight(200)
        ;

        static::assertSame('test_hash', $media->getHash());
        static::assertSame('http://localhost/foo/bar.jpg', $media->getUrl());
        static::assertSame('bar.jpg', $media->getName());
        static::assertSame('jpg', $media->getExtension());
        static::assertSame('image/jpeg', $media->getMime());
        static::assertSame(1536, $media->getBytes());
        static::assertSame('1.5 KB', $media->getSize());
        static::assertSame(300, $media->getWidth());
        static::assertSame(200, $media->getHeight());

        static::assertSame('http://localhost/foo/bar.jpg?hash=test_hash', (string) $media);
    }
}
