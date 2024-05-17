<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Form\Type\MediaFileType;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Media\Generic;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ChannelTypeExtension extends AbstractTypeExtension
{
    private ChannelRegistry $registry;

    public function __construct(ChannelRegistry $registry)
    {
        $this->registry = $registry;
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

        $view->vars['channel'] = $options['channel'];
        $view->vars['accept'] = \count($accept) ? implode(',', $accept) : '*';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('channel', Generic::class);
        $resolver->setAllowedTypes('channel', ['null', 'string', ChannelInterface::class]);

        $resolver->setNormalizer('channel', function (Options $options, $channel): ChannelInterface {
            if ($channel instanceof ChannelInterface) {
                return $channel;
            }

            $channel ??= Generic::class;
            if ($this->registry->has($channel)) {
                return $this->registry->get($channel);
            }

            return $this->registry->getByClass($channel);
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            MediaFileType::class,
            MediaType::class,
        ];
    }
}
