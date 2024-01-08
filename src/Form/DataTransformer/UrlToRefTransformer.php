<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\DataTransformer;

use Siganushka\GenericBundle\Form\DataTransformer\EntityToIdentifierTransformer;
use Siganushka\MediaBundle\Entity\Media;

class UrlToRefTransformer extends EntityToIdentifierTransformer
{
    /**
     * @param mixed $value
     */
    public function transform($value): string
    {
        dd(__METHOD__, $value);
    }

    /**
     * @param mixed $value
     */
    public function reverseTransform($value): ?Media
    {
        dd(__METHOD__, $value);
    }
}
