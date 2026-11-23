<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use Siganushka\MediaBundle\Form\MediaUploadType;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

class MediaUploadTypeTest extends TypeTestCase
{
    public function testBuildForm(): void
    {
        $form = $this->factory->create(MediaUploadType::class);

        static::assertSame(['rule', 'file'], array_keys($form->createView()->children));

        /** @var array<string, Rule> $choices */
        $choices = $form->get('rule')->getConfig()->getOption('choices');
        static::assertSame(['foo', 'bar'], array_keys($choices));

        // 未选择规则时，文件仅要求非空
        /** @var array<int, Constraint> $constraints */
        $constraints = $form->get('file')->getConfig()->getOption('constraints');
        static::assertCount(1, $constraints);
        static::assertInstanceOf(NotBlank::class, $constraints[0]);
    }

    public function testFileConstraintFollowsSelectedRule(): void
    {
        $form = $this->factory->create(MediaUploadType::class);
        $form->submit(['rule' => 'foo']);

        // 选择规则后，追加该规则自身的文件约束
        /** @var array<int, Constraint> $constraints */
        $constraints = $form->get('file')->getConfig()->getOption('constraints');
        static::assertCount(2, $constraints);
        static::assertInstanceOf(NotBlank::class, $constraints[0]);
        static::assertInstanceOf(File::class, $constraints[1]);
    }

    #[DataProvider('invalidRuleProvider')]
    public function testSubmitWithInvalidRuleIsInvalid(?string $rule, string $message): void
    {
        $form = $this->factory->create(MediaUploadType::class);
        $form->submit(['rule' => $rule]);

        static::assertFalse($form->isValid());

        $messages = array_map(
            static fn (FormError $item) => $item->getMessage(),
            iterator_to_array($form->get('rule')->getErrors())
        );

        static::assertContains($message, $messages);
    }

    public function testSubmitWithoutFileIsInvalid(): void
    {
        $form = $this->factory->create(MediaUploadType::class);
        $form->submit(['rule' => 'foo', 'file' => null]);

        static::assertFalse($form->isValid());
        static::assertGreaterThan(0, $form->get('file')->getErrors()->count());
    }

    protected function getTypes(): array
    {
        $registry = $this->createMock(RuleRegistry::class);
        $registry->method('all')->willReturn([
            'foo' => new Rule('foo'),
            'bar' => new Rule('bar'),
        ]);

        return [
            new MediaUploadType($registry),
        ];
    }

    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public static function invalidRuleProvider(): iterable
    {
        yield [null, 'Media rule should not be blank.'];
        yield ['baz', 'Media rule is not valid.'];
    }
}
