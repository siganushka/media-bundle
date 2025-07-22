<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Serializer\Normalizer\MediaNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MediaNormalizerTest extends TestCase
{
    protected MediaNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new MediaNormalizer(new ObjectNormalizer());
    }

    public function testNormalize(): void
    {
        $media = new Media();
        $media->setHash('test_hash');
        $media->setUrl('http://localhost/foo/bar/baz.jpg');
        $media->setName('baz.jpg');
        $media->setExtension('jpg');
        $media->setMime('image/jpeg');
        $media->setSize(1536);
        $media->setWidth(300);

        static::assertTrue($this->normalizer->supportsNormalization($media));
        static::assertSame('http://localhost/foo/bar/baz.jpg?hash=test_hash', $this->normalizer->normalize($media));
        static::assertSame([
            'hash' => 'test_hash',
            'url' => 'http://localhost/foo/bar/baz.jpg',
            'name' => 'baz.jpg',
            'extension' => 'jpg',
            'mime' => 'image/jpeg',
            'size' => 1536,
            'width' => 300,
            'height' => null,
        ], $this->normalizer->normalize($media, context: [
            ObjectNormalizer::ATTRIBUTES => ['hash', 'url', 'name', 'extension', 'mime', 'size', 'width', 'height'],
        ]));
    }
}
