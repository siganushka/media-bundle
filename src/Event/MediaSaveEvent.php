<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Rule;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    private string $hash;
    private ?Media $media = null;

    public function __construct(private readonly Rule $rule, private readonly \SplFileInfo $file)
    {
    }

    public function getRule(): Rule
    {
        return $this->rule;
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

        $fileHash = @md5_file($this->file->getPathname()) ?: throw new \RuntimeException('Unable to hash file.');
        $ruleHash = \sprintf('%s_%32s', $this->rule->alias, $fileHash);

        // [important] The same source file will generate different HASH in different rules.
        return $this->hash = md5($ruleHash);
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): self
    {
        $this->media = $media;

        return $this;
    }
}
