<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\File as AssertFile;

interface ChannelInterface extends \Stringable
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
     * 返回文件验证约束类型.
     */
    public function getConstraint(): AssertFile;

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
