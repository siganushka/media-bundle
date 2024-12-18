<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Utils;

class FileUtils
{
    /**
     * Create \SplFileInfo from remote url.
     *
     * @see https://www.jianshu.com/p/42e0c4304b60
     *
     * @param string $url       Remote file url
     * @param int    $timeoutMs Timeout in milliseconds
     */
    public static function createFromUrl(string $url, int $timeoutMs = 10000): \SplFileInfo
    {
        if (false === $curl = curl_init()) {
            throw new \RuntimeException('failed to initialize');
        }

        curl_setopt($curl, \CURLOPT_URL, $url);
        curl_setopt($curl, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, \CURLOPT_TIMEOUT_MS, $timeoutMs);

        $content = curl_exec($curl);
        if (!\is_string($content)) {
            throw new \RuntimeException(curl_error($curl));
        }

        // Close curl resource.
        curl_close($curl);

        return self::createFromContent($content, pathinfo($url, \PATHINFO_BASENAME));
    }

    /**
     * Create \SplFileInfo from Data URI.
     *
     * @see https://en.wikipedia.org/wiki/Data_URI_scheme
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URLs
     * @see https://github.com/symfony/serializer/blob/7.2/Normalizer/DataUriNormalizer.php#L88
     *
     * @param string      $dataUri  Data URI content
     * @param string|null $fileName Temporary file name
     */
    public static function createFromDataUri(string $dataUri, ?string $fileName = null): \SplFileInfo
    {
        if (!preg_match('/^data:([a-z0-9][a-z0-9\!\#\$\&\-\^\_\+\.]{0,126}\/[a-z0-9][a-z0-9\!\#\$\&\-\^\_\+\.]{0,126}(;[a-z0-9\-]+\=[a-z0-9\-]+)?)?(;base64)?,[a-z0-9\!\$\&\\\'\,\(\)\*\+\;\=\-\.\_\~\:\@\/\?\%\s]*\s*$/i', $dataUri)) {
            throw new \InvalidArgumentException('Invalid data URI file.');
        }

        [$_, $content] = array_pad(explode(',', $dataUri), 3, null);
        if (null === $content) {
            throw new \InvalidArgumentException('Invalid data URI file.');
        }

        return self::createFromContent(base64_decode($content), $fileName);
    }

    /**
     * Create \SplFileInfo from binary file content.
     *
     * @param string      $content  Binary file content
     * @param string|null $fileName Temporary file name
     */
    public static function createFromContent(string $content, ?string $fileName = null): \SplFileInfo
    {
        $file = \sprintf('%s/%s', sys_get_temp_dir(), $fileName ?? uniqid());

        file_put_contents($file, $content);
        if (!is_file($file)) {
            throw new \RuntimeException('Unable to save file.');
        }

        return new \SplFileInfo($file);
    }

    /**
     * 获取图像尺寸信息.
     *
     * @see https://www.php.net/manual/en/function.getimagesize.php
     *
     * @param \SplFileInfo $file 图像文件
     *
     * @return array{ 0: int, 1: int, 2: int, 3: string, bits?: int, channels?: int, mime: string } 图像尺寸信息
     *
     * @throws \RuntimeException 文件不存在或不是图像文件
     */
    public static function getImageSize(\SplFileInfo $file): array
    {
        if (!$file->isFile()) {
            throw new \RuntimeException('File not found.');
        }

        $result = @getimagesize($file->getPathname());
        if (false === $result) {
            throw new \RuntimeException('Unable to access file.');
        }

        return $result;
    }

    /**
     * 获取格式化后的文件大小.
     *
     * @see https://www.php.net/manual/zh/splfileinfo.getsize.php
     *
     * @param \SplFileInfo $file 文件
     *
     * @return string 格式化后的文件大小
     *
     * @throws \RuntimeException 获取文件大小失败或文件不存在
     */
    public static function getFormattedSize(\SplFileInfo $file): string
    {
        if (!$file->isFile()) {
            throw new \RuntimeException('File not found.');
        }

        $size = $file->getSize();
        if (false === $size) {
            throw new \RuntimeException('Unable to access file.');
        }

        return static::formatBytes($size);
    }

    /**
     * 格式化字节数.
     *
     * @param int $bytes 字节数
     *
     * @return string 格式化字节数
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0B';
        }

        $base = log($bytes, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];

        return round(1024 ** ($base - floor($base)), 2).($suffixes[(int) floor($base)] ?? '');
    }
}
