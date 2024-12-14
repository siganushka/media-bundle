<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractFileEvent extends Event
{
    public function __construct(private readonly \SplFileInfo $file)
    {
    }

    public function getFile(): \SplFileInfo
    {
        return $this->file;
    }
}
