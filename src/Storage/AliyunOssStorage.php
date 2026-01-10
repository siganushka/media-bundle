<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use OSS\Credentials\StaticCredentialsProvider;
use OSS\OssClient;

/**
 * @see https://help.aliyun.com/document_detail/31834.html
 */
class AliyunOssStorage extends AbstractStorage
{
    public const TIMEOUT = 'timeout';
    public const CONNECT_TIMEOUT = 'connect_timeout';
    public const MAX_RETRIES = 'max_retries';
    public const USE_SSL = 'use_ssl';

    public readonly OssClient $client;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, public readonly string $bucket, array $options = [])
    {
        if (!class_exists(OssClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "aliyuncs/oss-sdk-php" component. Try running "composer require aliyuncs/oss-sdk-php".', self::class));
        }

        $options[self::USE_SSL] ??= true;

        $provider = new StaticCredentialsProvider($accessKeyId, $accessKeySecret);
        $config = compact('provider', 'endpoint') + $options;

        $this->client = new OssClient($config);

        foreach ([
            self::TIMEOUT => $this->client->setTimeout(...),
            self::CONNECT_TIMEOUT => $this->client->setConnectTimeout(...),
            self::MAX_RETRIES => $this->client->setMaxTries(...),
            self::USE_SSL => $this->client->setUseSSL(...),
        ] as $name => $callable) {
            if (\array_key_exists($name, $options)) {
                \call_user_func($callable, $options[$name]);
            }
        }

        parent::__construct($options[self::PREFIX_DIR] ?? null);
    }

    public function doSave(\SplFileInfo $originFile, string $targetFileToSave): string
    {
        $result = $this->client->uploadFile($this->bucket, $targetFileToSave, $originFile->getPathname());

        $url = $result['info']['url'] ?? null;
        if ($url && \is_string($url)) {
            return $url;
        }

        throw new \LogicException('Invalid response.');
    }

    public function doDelete(string $path): void
    {
        $this->client->deleteObject($this->bucket, ltrim($path, '/'));
    }
}
