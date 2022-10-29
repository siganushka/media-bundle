<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\Contracts\Registry\AliasableInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;

interface ChannelInterface extends AliasableInterface
{
    /**
     * 返回新文件保存路径（不包含文件名）.
     *
     * @param File $file 文件对象
     *
     * @return string 新文件保存路径
     */
    public function getPathname(File $file): string;

    /**
     * 返回新文件名（包含后缀）.
     *
     * @param File $file 文件对象
     *
     * @return string 新文件名
     */
    public function getFilename(File $file): string;

    /**
     * 返回文件验证类型集合.
     *
     * @return array<int, Constraint>
     */
    public function getConstraints(): array;
}
