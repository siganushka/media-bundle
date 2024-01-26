<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use OSS\OssClient;
use Siganushka\MediaBundle\ChannelInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @see https://help.aliyun.com/document_detail/31834.html
 */
class AliyunOssStorage implements StorageInterface
{
    private OssClient $ossClient;
    private string $bucket;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, string $bucket)
    {
        if (!class_exists(OssClient::class)) {
            throw new \LogicException(sprintf('The "%s" class requires the "aliyuncs/oss-sdk-php" component. Try running "composer require aliyuncs/oss-sdk-php".', self::class));
        }

        $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $this->bucket = $bucket;
    }

    public function save(ChannelInterface $channel, File $file): string
    {
        $object = $channel->getTargetName($file);

        try {
            $result = $this->ossClient->uploadFile($this->bucket, $object, $file->getPathname());
        } catch (\Throwable $th) {
            // add logger...
            throw $th;
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
