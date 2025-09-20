<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Siganushka\GenericBundle\Form\DataTransformer\EntityToIdentifierTransformer;
use Siganushka\MediaBundle\Form\DataTransformer\MediaReferenceToHashTransformer;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly MediaRepository $repository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new MediaReferenceToHashTransformer(), true);
        $builder->addViewTransformer(new EntityToIdentifierTransformer($this->registry, $this->repository->getClassName(), 'hash'), true);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['icon'] = $options['icon'];
        $view->vars['style'] = $options['style'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'icon' => 'plus',
            'style' => null,
            'invalid_message' => 'This value is not a valid media reference.',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
