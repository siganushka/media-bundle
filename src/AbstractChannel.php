<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Mapping\GenericMetadata;

abstract class AbstractChannel implements ChannelInterface
{
    public function getTargetName(File $file): string
    {
        $extension = $file->guessExtension();
        if (!$extension) {
            throw new \RuntimeException('Unable to access file.');
        }

        $channel = str_replace('_', '-', $this->getAlias());

        // Like Git commit ID
        $event = new MediaSaveEvent($this, $file);
        $hash = mb_substr($event->getHash(), 0, 7);

        return sprintf('%s/%07s.%s', $channel, $hash, $extension);
    }

    public function getConstraints(): array
    {
        $metadata = new GenericMetadata();
        $this->loadConstraints($metadata);

        return $metadata->getConstraints();
    }

    public function onPreSave(File $file): void
    {
    }

    public function onPostSave(string $mediaUrl): void
    {
    }

    public function getAlias(): string
    {
        if (preg_match('~([^\\\\]+?)?$~i', static::class, $matches)) {
            return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $matches[1]));
        }

        return str_replace('\\', '_', strtolower(static::class));
    }

    public function __toString(): string
    {
        return $this->getAlias();
    }

    protected function loadConstraints(GenericMetadata $metadata): void
    {
    }
}
