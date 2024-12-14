<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Extension;

use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ChannelTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly ChannelRegistry $registry)
    {
    }

    /**
     * @param array{ channel: Channel } $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['channel'] = $options['channel'];
        $view->vars['accept'] = static::getAcceptFormFile($options['channel']->getConstraint());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('channel');
        $resolver->setAllowedTypes('channel', ['string', Channel::class]);

        $resolver->setNormalizer('channel', function (Options $options, string|Channel $channel): Channel {
            if (\is_string($channel)) {
                return $this->registry->get($channel);
            }

            return $channel;
        });
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
    public static function getAcceptFormFile(File $file): string
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
