<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaFileType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setNormalizer('constraints', function (Options $options, mixed $constraints): array {
            $constraints = \is_object($constraints) ? [$constraints] : (array) $constraints;

            $channel = $options['channel'] ?? null;
            if ($channel instanceof ChannelInterface) {
                array_push($constraints, ...$channel->getConstraints());
            }

            return $constraints;
        });
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
