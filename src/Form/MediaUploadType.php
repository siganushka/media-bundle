<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\ChannelRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MediaUploadType extends AbstractType
{
    public function __construct(private readonly ChannelRegistry $registry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', ChoiceType::class, [
                'label' => 'media.channel',
                'choices' => $this->registry->all(),
                'choice_value' => fn (?string $choice) => $choice,
                'choice_translation_domain' => false,
                'constraints' => new NotBlank(),
            ])
        ;

        $builder->get('channel')
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'formModifier'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'formModifier'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('csrf_protection', false);
    }

    public function formModifier(FormEvent $event): void
    {
        $form = $event->getForm();
        $channel = $event instanceof PostSubmitEvent ? $form->getData() : $event->getData();

        $constraints = [new NotBlank()];
        if ($channel instanceof Channel) {
            $constraints[] = $channel->getConstraint();
        }

        /** @var FormInterface */
        $parent = $form->getParent();
        $parent->add('file', FileType::class, [
            'label' => 'media.file',
            'constraints' => $constraints,
        ]);
    }
}
