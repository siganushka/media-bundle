<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class MediaFileType extends AbstractType
{
    public function getParent(): string
    {
        return FileType::class;
    }
}
