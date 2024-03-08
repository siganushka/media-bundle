<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\Contracts\Registry\AliasableInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;

interface ChannelInterface extends AliasableInterface, \Stringable
{
    /**
     * 返回新文件名存储名称.
     *
     * @param File $file 文件对象
     *
     * @return string 新文件名存储名称
     */
    public function getTargetName(File $file): string;

    /**
     * 返回文件验证类型集合.
     *
     * @return array<int, Constraint>
     */
    public function getConstraints(): array;

    /**
     * 文件保存前置事件回调.
     *
     * @param File $file 文件对象
     */
    public function onPreSave(File $file): void;

    /**
     * 文件保存后置事件回调.
     *
     * @param string $mediaUrl 文件的 URL
     */
    public function onPostSave(string $mediaUrl): void;
}
