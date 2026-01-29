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
    public const CNAME = 'cname';

    public readonly OssClient $client;
    public readonly bool $cname;

    public function __construct(string $accessKeyId, string $accessKeySecret, public readonly string $endpoint, public readonly string $bucket, array $options = [])
    {
        if (!class_exists(OssClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "aliyuncs/oss-sdk-php" component. Try running "composer require aliyuncs/oss-sdk-php".', self::class));
        }

        $options[self::USE_SSL] ??= true;
        $this->cname = $options[self::CNAME] ?? false;

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

    public function doSave(\SplFileInfo $originFile, string $targetFile): string
    {
        $this->client->uploadFile($this->bucket, self::normalize($targetFile), $originFile->getPathname());

        return $this->buildUrl($targetFile);
    }

    public function doDelete(string $path): void
    {
        $this->client->deleteObject($this->bucket, self::normalize($path));
    }

    public function buildUrl(string $targetFile): string
    {
        $scheme = $this->client->isUseSSL() ? 'https' : 'http';
        $domain = parse_url($this->endpoint, \PHP_URL_HOST) ?? $this->endpoint;

        return $this->cname
            ? \sprintf('%s://%s/%s', $scheme, $domain, self::normalize($targetFile))
            : \sprintf('%s://%s.%s/%s', $scheme, $this->bucket, $domain, self::normalize($targetFile));
    }
}
