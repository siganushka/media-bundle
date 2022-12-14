<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Form\DataTransformer\ChannelToAliasTransformer;
use Siganushka\MediaBundle\Form\Type\MediaFileType;
use Siganushka\MediaBundle\Form\Type\MediaUrlType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChannelTypeExtension extends AbstractTypeExtension
{
    private ChannelToAliasTransformer $channelToAliasTransformer;

    public function __construct(ChannelRegistry $registry)
    {
        $this->channelToAliasTransformer = new ChannelToAliasTransformer($registry);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('channel')
            ->default(null)
            ->allowedTypes('null', 'string', ChannelInterface::class)
            ->normalize(function (Options $options, ?string $channel): ?ChannelInterface {
                if ($channel instanceof ChannelInterface) {
                    return $channel;
                }

                return $this->channelToAliasTransformer->reverseTransform($channel);
            })
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            MediaFileType::class,
            MediaUrlType::class,
        ];
    }
}
