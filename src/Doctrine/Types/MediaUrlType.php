<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class MediaUrlType extends Type
{
    public const NAME = 'media_url';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function getName()
    {
        return static::NAME;
    }
}
