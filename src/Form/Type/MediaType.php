<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('channel', MediaChannelType::class, [
                'label' => 'media.channel',
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
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function formModifier(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event instanceof PostSubmitEvent ? $form->getData() : $event->getData();

        $constraints = $data instanceof ChannelInterface ? $data->getConstraints() : [];
        array_unshift($constraints, new NotBlank());

        /** @var FormInterface */
        $parent = $form->getParent();
        $parent->add('file', MediaFileType::class, [
            'label' => 'media.file',
            'constraints' => $constraints,
        ]);
    }
}
