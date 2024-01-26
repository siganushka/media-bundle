<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Serializer\Normalizer;

use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class MediaNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private NormalizerInterface $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * @param Media|mixed $object
     *
     * @return \ArrayObject|array|scalar|null
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (\array_key_exists(AbstractNormalizer::ATTRIBUTES, $context)) {
            return $this->normalizer->normalize($object, $format, $context);
        }

        $url = $object->getUrl();
        $hash = $object->getHash();

        return (null === $url || null === $hash) ? null : sprintf('%s?hash=%s', $url, $hash);
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Media;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
