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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelTypeExtension extends AbstractTypeExtension
{
    private ChannelToAliasTransformer $transformer;

    public function __construct(ChannelRegistry $registry)
    {
        $this->transformer = new ChannelToAliasTransformer($registry);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('channel')
            ->default(Generic::class)
            ->allowedTypes('string', ChannelInterface::class)
            ->normalize(function (Options $options, $channel): ?ChannelInterface {
                if ($channel instanceof ChannelInterface) {
                    return $channel;
                }

                return $this->transformer->reverseTransform($channel);
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
