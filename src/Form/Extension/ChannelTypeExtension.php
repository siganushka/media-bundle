<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Form\DataTransformer\ChannelToAliasTransformer;
use Siganushka\MediaBundle\Form\Type\MediaFileType;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Media\Generic;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelTypeExtension extends AbstractTypeExtension
{
    private ChannelToAliasTransformer $transformer;

    public function __construct(ChannelRegistry $registry)
    {
        $this->transformer = new ChannelToAliasTransformer($registry);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['channel'] = $options['channel'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('channel')
            ->default(Generic::class)
            ->allowedTypes('null', 'string', ChannelInterface::class)
            ->normalize(function (Options $options, $channel): ChannelInterface {
                if ($channel instanceof ChannelInterface) {
                    return $channel;
                }

                return $this->transformer->reverseTransform($channel)
                    ?? $this->transformer->reverseTransform(Generic::class);
            })
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            MediaFileType::class,
            MediaType::class,
        ];
    }
}
