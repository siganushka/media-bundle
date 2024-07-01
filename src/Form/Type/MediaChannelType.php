<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\ChannelRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaChannelType extends AbstractType
{
    public function __construct(private ChannelRegistry $registry)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->registry->all(),
            'choice_value' => fn (?string $choice) => $choice,
            'choice_translation_domain' => false,
            'invalid_message' => 'media.channel.invalid',
            'invalid_message_parameters' => fn (Options $options) => ['{{ accepted_values }}' => implode(', ', $options['choices'])],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
