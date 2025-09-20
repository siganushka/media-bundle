<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<string, string>
 */
class MediaReferenceToHashTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        return $value;
    }

    public function reverseTransform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $queryString = parse_url($value, \PHP_URL_QUERY);
        if (!\is_string($queryString)) {
            return $value;
        }

        parse_str($queryString, $result);
        if (isset($result['hash']) && \is_string($result['hash'])) {
            return $result['hash'];
        }

        return $value;
    }
}
