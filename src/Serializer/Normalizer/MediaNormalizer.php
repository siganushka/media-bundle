<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Serializer\Normalizer;

use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MediaNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    /**
     * @param Media $object
     */
    public function normalize($object, string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (\array_key_exists(AbstractNormalizer::ATTRIBUTES, $context)) {
            return $this->normalizer->normalize($object, $format, $context);
        }

        $url = $object->getUrl();
        $hash = $object->getHash();

        return \is_string($url) && \is_string($hash) ? \sprintf('%s?hash=%s', $url, $hash) : null;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
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
