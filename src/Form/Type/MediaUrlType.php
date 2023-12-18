<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MediaUrlType extends AbstractType
{
    private MediaRepository $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['channel'] = $options['channel']->getAlias();
    }

    /**
     * @psalm-suppress MissingClosureParamType
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setNormalizer('constraints', function (Options $options, $constraints): array {
            $constraints = \is_object($constraints) ? [$constraints] : (array) $constraints;
            $constraints[] = new Callback([$this, 'validateMediaUrl'], null, $options['channel']);

            return $constraints;
        });
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function validateMediaUrl(?string $mediaUrl, ExecutionContextInterface $context, ChannelInterface $channel): void
    {
        if (null === $mediaUrl) {
            return;
        }

        $media = $this->matchingMedia($mediaUrl);
        if ($media && $media->isChannel($channel) && str_starts_with($mediaUrl, $media->getUrl())) {
            return;
        }

        $message = $media && !$media->isChannel($channel)
            ? 'media_url.not_match'
            : 'media_url.invalid';

        $context->buildViolation($message)
            ->setParameter('{{ value }}', $mediaUrl)
            ->addViolation()
        ;
    }

    private function matchingMedia(string $mediaUrl): ?Media
    {
        $queryString = parse_url($mediaUrl, \PHP_URL_QUERY);
        if (null === $queryString) {
            return null;
        }

        parse_str($queryString, $queries);
        if (empty($reference = $queries[Media::REFERENCE_QUERY] ?? null)) {
            return null;
        }

        return $this->mediaRepository->findOneBy([Media::REFERENCE_FIELD => $reference]);
    }
}
