<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Form\DataTransformer\MediaToReferenceTransformer;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MediaToReferenceTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $media = new Media();
        $media->setHash('test_hash');

        $transformer = $this->createDataTransformer();
        static::assertNull($transformer->transform(null));
        static::assertSame('test_hash', $transformer->transform($media));
    }

    public function testReverseTransform(): void
    {
        $transformer = $this->createDataTransformer();
        static::assertNull($transformer->reverseTransform(''));
        static::assertNull($transformer->reverseTransform(null));

        $media = $transformer->reverseTransform('test_hash');
        static::assertInstanceOf(Media::class, $media);
        static::assertSame('test_hash', $media->getHash());

        $media = $transformer->reverseTransform('http://localhost/test.png?hash=test_hash');
        static::assertInstanceOf(Media::class, $media);
        static::assertSame('test_hash', $media->getHash());
    }

    public function testReverseTransformNoHashException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('An object with identifier key "hash" and value "http://localhost/test.png" does not exist');

        $transformer = $this->createDataTransformer();
        $transformer->reverseTransform('http://localhost/test.png');
    }

    private function createDataTransformer(): MediaToReferenceTransformer
    {
        $media = new Media();
        $media->setHash('test_hash');

        $repository = $this->createMock(MediaRepository::class);
        $repository->expects(static::any())
            ->method('getClassName')
            ->willReturn(Media::class)
        ;

        $repository->expects(static::any())
            ->method('findOneBy')
            ->willReturnCallback(fn (array $value) => $value === ['hash' => 'test_hash'] ? $media : null)
        ;

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager->expects(static::any())
            ->method('getRepository')
            ->willReturn($repository)
        ;

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(static::any())
            ->method('getManagerForClass')
            ->willReturn($objectManager)
        ;

        return new MediaToReferenceTransformer($managerRegistry, $repository);
    }
}
