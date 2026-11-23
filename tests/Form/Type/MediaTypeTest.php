<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Form\Type;

use Siganushka\MediaBundle\Form\DataTransformer\MediaToReferenceTransformer;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;
use Symfony\Component\Form\Test\TypeTestCase;

class MediaTypeTest extends TypeTestCase
{
    public function testBuildForm(): void
    {
        $media = new TestMedia();
        $media->setHash('test_hash');

        $form = $this->factory->create(MediaType::class, $media, ['data_class' => null]);

        static::assertSame('This value is not a valid media reference.', $form->getConfig()->getOption('invalid_message'));
        static::assertSame('test_hash', $form->createView()->vars['value']);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(MediaType::class, new TestMedia(), ['data_class' => null]);
        $form->submit('new_hash');

        static::assertTrue($form->isSynchronized());

        /** @var TestMedia $media */
        $media = $form->getData();
        static::assertInstanceOf(TestMedia::class, $media);
        static::assertSame('new_hash', $media->getHash());
    }

    public function testSubmitEmptyValue(): void
    {
        $form = $this->factory->create(MediaType::class, new TestMedia(), ['data_class' => null]);
        $form->submit('');

        static::assertTrue($form->isSynchronized());
        static::assertNull($form->getData());
    }

    protected function getTypes(): array
    {
        $transformer = $this->createMock(MediaToReferenceTransformer::class);
        $transformer->method('transform')->willReturnCallback(
            static fn (?TestMedia $media) => $media?->getHash()
        );
        $transformer->method('reverseTransform')->willReturnCallback(
            static fn (?string $hash) => null !== $hash && '' !== $hash ? (new TestMedia())->setHash($hash) : null
        );

        return [
            new MediaType($transformer),
        ];
    }
}
