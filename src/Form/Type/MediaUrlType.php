<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

    public function validateMediaUrl(?string $mediaUrl, ExecutionContextInterface $context, ?ChannelInterface $channel): void
    {
        if (null === $mediaUrl) {
            return;
        }

        $media = $this->matchingMedia($mediaUrl);
        if ($media && (null === $channel || $media->isChannel($channel->getAlias())) && str_starts_with($mediaUrl, $media->getUrl())) {
            return;
        }

        $message = $media && $channel && !$media->isChannel($channel->getAlias())
            ? 'This media url channel is not match.'
            : 'This media url is not valid.';

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
