<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use OSS\OssClient;

/**
 * @see https://help.aliyun.com/document_detail/31834.html
 */
class AliyunOssStorage extends AbstractStorage
{
    private readonly OssClient $ossClient;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, private readonly string $bucket, ?string $prefix = null)
    {
        if (!class_exists(OssClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "aliyuncs/oss-sdk-php" component. Try running "composer require aliyuncs/oss-sdk-php".', self::class));
        }

        $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);

        parent::__construct($prefix);
    }

    public function doSave(\SplFileInfo $originFile, string $targetFileToSave): string
    {
        $result = $this->ossClient->uploadFile($this->bucket, $targetFileToSave, $originFile->getPathname());

        $url = $result['info']['url'] ?? null;
        if ($url && \is_string($url)) {
            return $url;
        }

        throw new \LogicException('Invalid response.');
    }

    public function doDelete(string $path): void
    {
        $this->ossClient->deleteObject($this->bucket, ltrim($path, '/'));
    }
}
