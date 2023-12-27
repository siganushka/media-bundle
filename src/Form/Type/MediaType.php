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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
        $view->vars['style'] = $options['style'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setNormalizer('constraints', function (Options $options, $constraints): array {
            $constraints = \is_object($constraints) ? [$constraints] : (array) $constraints;
            $constraints[] = new Callback([$this, 'validate'], null, $options);

            return $constraints;
        });

        $resolver->setDefaults([
            'style' => fn (Options $options) => sprintf('width: %s; height: %s', $options['width'], $options['height']),
            'width' => '100px',
            'height' => '100px',
            'mismatch_message' => 'media.mismatch',
        ]);

        $resolver->setAllowedTypes('style', 'string');
        $resolver->setAllowedTypes('width', 'string');
        $resolver->setAllowedTypes('height', 'string');
        $resolver->setAllowedTypes('mismatch_message', 'string');
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function validate(?Media $media, ExecutionContextInterface $context, Options $options): void
    {
        if ($media && !$media->isChannel($options['channel'])) {
            $context->buildViolation($options['mismatch_message'])
                ->addViolation()
            ;
        }
    }
}