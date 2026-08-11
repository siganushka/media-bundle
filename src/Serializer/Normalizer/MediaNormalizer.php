<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Serializer\Normalizer;

use Siganushka\MediaBundle\Model\MediaInterface;
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
     * @param MediaInterface $object
     */
    public function normalize($object, ?string $format = null, array $context = []): array|string
    {
        $asReference = $context[self::AS_REFERENCE] ?? true;
        if ($asReference) {
            return $object->__toString();
        }

        // Ignore id by default.
        $context[AbstractNormalizer::IGNORED_ATTRIBUTES] ??= ['id'];

        /** @var array */
        $data = $this->normalizer->normalize($object, $format, $context);

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MediaInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            MediaInterface::class => true,
        ];
    }
}
