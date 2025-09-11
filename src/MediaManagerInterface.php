<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Entity\Media;

interface MediaManagerInterface
{
    public function save(string|Channel $channel, string|\SplFileInfo $file): Media;
}
