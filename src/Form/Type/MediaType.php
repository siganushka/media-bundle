<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Siganushka\GenericBundle\Form\DataTransformer\EntityToIdentifierTransformer;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @implements DataTransformerInterface<string, mixed>
 */
class MediaType extends AbstractType implements DataTransformerInterface
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this, true);

        // Using addViewTransformer to transformer object for view
        $builder->addViewTransformer(new EntityToIdentifierTransformer($this->registry, Media::class, 'hash'), true);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['style'] = $options['style'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('style', null);
        $resolver->setAllowedTypes('style', ['null', 'string']);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function transform($value): ?string
    {
        return $value;
    }

    public function reverseTransform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $queryString = parse_url($value, \PHP_URL_QUERY);
        if (!\is_string($queryString)) {
            return $value;
        }

        parse_str($queryString, $result);
        if (isset($result['hash']) && \is_string($result['hash'])) {
            return $result['hash'];
        }

        return $value;
    }
}
