<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Media\Generic;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly ChannelRegistry $registry)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (($channel = $options['channel']) instanceof ChannelInterface) {
            // Pass data-attr to templates.
            $view->vars['channel'] = $channel;

            // Guesses HTML accept from channel constraint (twig only)
            // @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/accept
            // @see https://www.iana.org/assignments/media-types/media-types.xhtml
            $view->vars['accept'] = implode(', ', (array) $channel->getConstraint()->mimeTypes);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('channel', Generic::class);
        $resolver->setAllowedTypes('channel', ['null', 'string', ChannelInterface::class]);

        $resolver->setNormalizer('channel', function (Options $options, string|ChannelInterface|null $channel): ChannelInterface {
            if ($channel instanceof ChannelInterface) {
                return $channel;
            }

            return $this->registry->get($channel ?? Generic::class);
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            MediaType::class,
        ];
    }
}
