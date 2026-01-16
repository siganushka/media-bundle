<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\Form\DataTransformer\MediaToReferenceTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaType extends AbstractType
{
    public function __construct(private readonly MediaToReferenceTransformer $transformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('invalid_message', 'This value is not a valid media reference.');
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
