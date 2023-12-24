<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaFileType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setNormalizer('constraints', function (Options $options, $constraints): array {
            $constraints = \is_object($constraints) ? [$constraints] : (array) $constraints;
            array_push($constraints, ...$options['channel']->getConstraints());

            return $constraints;
        });
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
