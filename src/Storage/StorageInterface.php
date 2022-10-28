<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\HttpFoundation\File\File;

interface StorageInterface
{
    public function save(ChannelInterface $channel, File $media): string;
}
