<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Siganushka\GenericBundle\Form\DataTransformer\EntityToIdentifierTransformer;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MediaType extends AbstractType
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // using addViewTransformer to transformer object for view
        $builder->addViewTransformer(new EntityToIdentifierTransformer($this->registry, Media::class, 'hash'));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        // Guesses HTML accept from channel constraints (twig only)
        // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/accept
        // @see https://www.iana.org/assignments/media-types/media-types.xhtml
        $accept = [];
        foreach ($options['channel']->getConstraints() as $constraint) {
            if ($constraint instanceof File && $constraint->mimeTypes) {
                $accept = array_merge($accept, (array) $constraint->mimeTypes);
            }
        }

        $view->vars['style'] = $options['style'];
        $view->vars['accept'] = \count($accept) ? implode(',', $accept) : '*';
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
}
