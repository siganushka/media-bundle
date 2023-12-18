<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Form\DataTransformer\ChannelToAliasTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaChannelType extends AbstractType
{
    private ChannelRegistry $registry;

    public function __construct(ChannelRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new ChannelToAliasTransformer($this->registry));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => $this->registry->getServiceIds(),
            'choice_label' => fn (string $choice) => $choice,
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
