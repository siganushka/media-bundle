<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValidatorTypeExtension extends ChannelTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setNormalizer('constraints', function (Options $options, mixed $constraints) {
            $constraints = \is_object($constraints) ? [$constraints] : (array) $constraints;

            $channel = $options['channel'] ?? null;
            if ($channel instanceof ChannelInterface) {
                array_push($constraints, ...$channel->getConstraints());
            }

            return $constraints;
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            FileType::class,
        ];
    }
}
