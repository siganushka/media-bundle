<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Form\Extension;

use Siganushka\MediaBundle\Form\DataTransformer\MediaToReferenceTransformer;
use Siganushka\MediaBundle\Form\Extension\RuleTypeExtension;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class RuleTypeExtensionTest extends TypeTestCase
{
    public function testGetAccept(): void
    {
        $file = new File();
        static::assertSame('*', RuleTypeExtension::getAcceptFromFile($file));

        $file = new File(mimeTypes: 'application/xml');
        static::assertSame('application/xml', RuleTypeExtension::getAcceptFromFile($file));

        $file = new File(mimeTypes: 'application/json', extensions: [
            'jpg',
            'txt' => 'text/plain',
            'xml' => ['text/xml', 'application/xml'],
        ]);
        static::assertSame('application/json,.jpg,text/plain,text/xml,application/xml', RuleTypeExtension::getAcceptFromFile($file));

        $file = new Image();
        static::assertSame('image/*', RuleTypeExtension::getAcceptFromFile($file));

        $file = new Image(mimeTypes: ['image/jpg', 'image/png']);
        static::assertSame('image/jpg,image/png', RuleTypeExtension::getAcceptFromFile($file));
    }

    public function testBuildView(): void
    {
        $form = $this->factory->create(MediaType::class, null, [
            'data_class' => null,
            'rule' => 'foo',
        ]);

        $view = $form->createView();

        static::assertSame('foo', $view->vars['rule']);
        static::assertSame('image/*', $view->vars['accept']);
        static::assertSame('plus', $view->vars['icon']);
        static::assertNull($view->vars['style']);
    }

    public function testBuildViewWithRuleInstance(): void
    {
        $rule = new Rule('bar', Image::class, ['mimeTypes' => ['image/png']]);

        $form = $this->factory->create(MediaType::class, null, [
            'data_class' => null,
            'rule' => $rule,
        ]);

        $view = $form->createView();

        static::assertSame('bar', $view->vars['rule']);
        static::assertSame('image/png', $view->vars['accept']);
    }

    protected function getTypes(): array
    {
        return [
            new MediaType($this->createMock(MediaToReferenceTransformer::class)),
        ];
    }

    protected function getTypeExtensions(): array
    {
        $registry = $this->createMock(RuleRegistry::class);
        $registry->method('get')
            ->willReturnCallback(static fn (string $alias) => new Rule($alias, Image::class, [
                'mimeTypes' => ['image/*'],
            ]))
        ;

        return [
            new RuleTypeExtension($registry),
        ];
    }
}
