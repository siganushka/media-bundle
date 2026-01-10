<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Siganushka\GenericBundle\Form\DataTransformer\EntityToIdentifierTransformer;

class MediaToReferenceTransformer extends EntityToIdentifierTransformer
{
    public function __construct(ManagerRegistry $registry, string $className)
    {
        parent::__construct($registry, $className, 'hash');
    }

    public function transform(mixed $value): mixed
    {
        return parent::transform($value);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (\is_string($value) && \is_string($qs = parse_url($value, \PHP_URL_QUERY))) {
            parse_str($qs, $result);
            if (\is_string($result['hash'] ?? null)) {
                return parent::reverseTransform($result['hash']);
            }
        }

        return parent::reverseTransform($value);
    }
}
