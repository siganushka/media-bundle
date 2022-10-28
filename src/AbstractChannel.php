<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Mapping\GenericMetadata;

abstract class AbstractChannel implements ChannelInterface
{
    /**
     * 返回新文件保存路径（包含文件名）.
     *
     * 为了避免同一目录下子目录/文件数量过多，使用渠道+年月格式的二级目录存储文件
     * 考虑到文件在上传阶段已去重，为了保持文件 URL 尽可能短，因此使用 CRC32
     * 作为文件名加上二级目录之后碰撞概率可以接受
     *
     * @param File $media 文件对象
     *
     * @return string 新文件保存路径（包含文件名）
     */
    public function getNewFile(File $media): string
    {
        $directory = str_replace('_', '-', $this->getAlias());

        $name = hash_file('CRC32', $media->getRealPath());
        if (false === $name) {
            throw new \RuntimeException('Unable to hash file.');
        }

        if (null === $extension = $media->guessExtension()) {
            throw new \RuntimeException('Unable to guess extension.');
        }

        return sprintf('/%s/%s/%s.%s', $directory, date('ym'), $name, $extension);
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
