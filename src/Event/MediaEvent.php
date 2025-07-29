<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Channel;
use Symfony\Contracts\EventDispatcher\Event;

class MediaEvent extends Event
{
    private ?string $hash = null;

    public function __construct(private readonly Channel $channel, private readonly \SplFileInfo $file)
    {
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    public function getFile(): \SplFileInfo
    {
        return $this->file;
    }

    public function getHash(): string
    {
        if (isset($this->hash)) {
            return $this->hash;
        }

        $fileHash = md5_file($this->file->getPathname())
            ?: throw new \RuntimeException('Unable to hash file.');

        // [important] The same source file will generate different HASH in different channels.
        return $this->hash = md5(\sprintf('%s_%32s', $this->channel->alias, $fileHash));
    }
}
