<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form;

use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MediaUploadType extends AbstractType
{
    public function __construct(private readonly RuleRegistry $registry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rule', ChoiceType::class, [
                'label' => 'media.rule',
                'choices' => $this->registry->all(),
                'choice_value' => fn (?string $choice) => $choice,
                'choice_translation_domain' => false,
                'constraints' => new NotBlank(),
            ])
        ;

        $builder->get('rule')
            ->addEventListener(FormEvents::PRE_SET_DATA, $this->formModifier(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->formModifier(...))
        ;
    }

    public function formModifier(FormEvent $event): void
    {
        $form = $event->getForm();
        $rule = $event instanceof PostSubmitEvent ? $form->getData() : $event->getData();

        $constraints = [new NotBlank()];
        if ($rule instanceof Rule) {
            $constraints[] = $rule->getConstraint();
        }

        /** @var FormInterface */
        $parent = $form->getParent();
        $parent->add('file', FileType::class, [
            'label' => 'media.file',
            'constraints' => $constraints,
        ]);
    }
}
