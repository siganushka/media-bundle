<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class RuleTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly RuleRegistry $registry)
    {
    }

    /**
     * @param array{ rule: Rule } $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['rule'] = $options['rule'];
        $view->vars['accept'] = static::getAcceptFromFile($options['rule']->getConstraint());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('rule');
        $resolver->setAllowedTypes('rule', ['string', Rule::class]);

        $resolver->setNormalizer('rule', fn (Options $options, string|Rule $rule): Rule => $rule instanceof Rule ? $rule : $this->registry->get($rule));
    }

    public static function getExtendedTypes(): iterable
    {
        return [
            MediaType::class,
        ];
    }

    /**
     * Gets HTML input file accept from file constraint.
     *
     * @see https://symfony.com/blog/new-in-symfony-6-2-improved-file-validator
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/accept
     * @see https://www.iana.org/assignments/media-types/media-types.xhtml
     */
    public static function getAcceptFromFile(File $file): string
    {
        $accepts = (array) $file->mimeTypes;

        foreach ((array) $file->extensions as $key => $value) {
            if (\is_string($key)) {
                array_push($accepts, ...(array) $value);
            } else {
                $accepts[] = '.'.$value;
            }
        }

        return \count($accepts) ? implode(',', $accepts) : '*';
    }
}
