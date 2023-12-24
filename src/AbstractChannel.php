<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Mapping\GenericMetadata;

abstract class AbstractChannel implements ChannelInterface
{
    public function getFilepath(File $file): string
    {
        return sprintf('%s/%s', str_replace('_', '-', (string) $this), date('Ym'));
    }

    public function getFilename(File $file): string
    {
        $path = $file->getPathname();
        $extension = $file->guessExtension();

        if ($path && $extension && $name = hash_file('CRC32', $path)) {
            return sprintf('%s.%s', $name, $extension);
        }

        throw new \RuntimeException('Unable to read file.');
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
