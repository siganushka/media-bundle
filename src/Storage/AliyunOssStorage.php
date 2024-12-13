<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use OSS\OssClient;

/**
 * @see https://help.aliyun.com/document_detail/31834.html
 */
class AliyunOssStorage implements StorageInterface
{
    private readonly OssClient $ossClient;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, private readonly string $bucket)
    {
        if (!class_exists(OssClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "aliyuncs/oss-sdk-php" component. Try running "composer require aliyuncs/oss-sdk-php".', self::class));
        }

        $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    }

    public function save(string|\SplFileInfo $origin, string $target): string
    {
        if (\is_string($origin)) {
            $origin = new \SplFileInfo($origin);
        }

        try {
            $result = $this->ossClient->uploadFile($this->bucket, $target, $origin->getPathname());
        } catch (\Throwable $th) {
            // add logger...
            throw $th;
        }

        if ($origin->isFile()) {
            @unlink($origin->getPathname());
        }

        if (isset($result['info']['url'])) {
            return $result['info']['url'];
        }

        throw new \LogicException('Invalid response.');
    }

    public function delete(string $url): void
    {
        $object = parse_url($url, \PHP_URL_PATH);
        if (null === $object || false === $object) {
            throw new \RuntimeException('Unable parse file.');
        }

        $this->ossClient->deleteObject($this->bucket, ltrim($object, '/'));
    }
}
