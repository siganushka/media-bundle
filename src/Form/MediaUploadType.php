<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form;

use Siganushka\MediaBundle\Form\Type\MediaChannelType;
use Siganushka\MediaBundle\Form\Type\MediaFileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MediaUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', MediaChannelType::class, [
                'label' => 'media.channel',
                'placeholder' => 'generic.choice',
                'constraints' => new NotBlank(null, 'media.channel.not_blank'),
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

        /** @var FormInterface */
        $form = $form->getParent();
        $form->add('file', MediaFileType::class, [
            'label' => 'media.file',
            'channel' => $channel,
            'constraints' => new NotBlank(null, 'media.file.not_blank'),
        ]);
    }
}
