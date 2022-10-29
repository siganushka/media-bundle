<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\HttpFoundation\File\File;

interface StorageInterface
{
    /**
     * 返回保存后文件的 URL.
     *
     * @param ChannelInterface $channel 存储规则
     * @param File             $file    文件对象
     *
     * @return string 文件的 URL
     */
    public function save(ChannelInterface $channel, File $file): string;
}
