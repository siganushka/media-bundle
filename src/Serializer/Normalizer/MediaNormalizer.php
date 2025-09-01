<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Serializer\Normalizer;

use Automattic\WooCommerce\GoogleListingsAndAds\Notes\AbstractNote;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MediaNormalizer implements NormalizerInterface
{
    public const AS_REFERENCE = 'media_as_reference';

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer)
    {
    }

    /**
     * @param Media $object
     */
    public function normalize($object, ?string $format = null, array $context = []): array|string
    {
        $asReference = $context[self::AS_REFERENCE] ?? true;
        if ($asReference && !\array_key_exists(AbstractNormalizer::ATTRIBUTES, $context)) {
            return $object->__toString();
        }

        /** @var array|string */
        $data = $this->normalizer->normalize($object, $format, array_merge_recursive($context, [
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['id'],
        ]));

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Media;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Media::class => true,
        ];
    }
}
