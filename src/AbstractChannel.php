<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Mapping\GenericMetadata;

abstract class AbstractChannel implements ChannelInterface
{
    /**
     * 为了避免同一目录下子目录/文件数量过多，使用 Channel+Date 格式的二级目录存储文件
     * 考虑到文件在上传阶段已去重，为了保持文件 URL 尽可能短，因此使用 CRC32
     * 作为文件名加上二级目录之后碰撞概率可以接受.
     */
    public function getPathname(File $file): string
    {
        return sprintf('%s/%s', str_replace('_', '-', $this->getAlias()), date('ym'));
    }

    public function getFilename(File $file): string
    {
        $path = $file->getRealPath();
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

    public function getAlias(): string
    {
        if (preg_match('~([^\\\\]+?)?$~i', static::class, $matches)) {
            return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $matches[1]));
        }

        return str_replace('\\', '_', strtolower(static::class));
    }

    protected function loadConstraints(GenericMetadata $metadata): void
    {
    }
}
