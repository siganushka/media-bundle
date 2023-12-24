<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Util\FormUtil;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaType extends AbstractType
{
    private MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // using addViewTransformer to transformer object for view
        $builder->addViewTransformer(new CallbackTransformer(
            fn ($value) => $value instanceof Media ? $value->getHash() : null,
            function ($value) use ($options) {
                if (FormUtil::isEmpty($value)) {
                    return;
                }

                $media = $this->mediaRepository->findOneBy(['hash' => $value, 'channel' => $options['channel']]);
                if ($media) {
                    return $media;
                }

                $exception = new TransformationFailedException(sprintf('An object with identifier key "hash" and value "%s" does not exist!', (string) $value));
                $exception->setInvalidMessage($options['mismatch_message']);

                throw $exception;
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['style'] = $options['style'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
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
}
