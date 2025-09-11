<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Entity\Media;

interface MediaManagerInterface
{
    public function save(string|Rule $rule, string|\SplFileInfo $file): Media;
}
