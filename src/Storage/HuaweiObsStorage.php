<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Obs\ObsClient;

/**
 * @see https://support.huaweicloud.com/obs/index.html
 */
class HuaweiObsStorage extends AbstractStorage
{
    public readonly ObsClient $client;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, public readonly string $bucket, array $options = [])
    {
        if (!class_exists(ObsClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "obs/esdk-obs-php" component. Try running "composer require obs/esdk-obs-php".', self::class));
        }

        /*
         * 由于 obs/esdk-obs-php 源码中对 GuzzleHttp\Client 的初始化参数 timeout=0 使用了硬编码，因此无法通过外部
         * 设置 GuzzleHttp\Client 的超时时间，这在某些情况下 CURL 会抛出 Operation timed out after xxx milliseconds with xxx...
         * 异常 (timeout=0 在不同环境、版本中处理不一致，某些情况下会回退为 10 秒)，因此这里通过反射强制 GuzzleHttp\Client
         * 超时时间与 obs/esdk-obs-php 本身的超时时间一致，如果未来 obs/esdk-obs-php 在某个版本中移除硬编码，则删除此处。
         *
         * @see https://github.com/huaweicloud/huaweicloud-sdk-php-obs/blob/master/Obs/ObsClient.php#L329
         * @see https://curl.se/libcurl/c/libcurl-errors.html
         */
        $this->client = new class($accessKeyId, $accessKeySecret, $endpoint, $options) extends ObsClient {
            public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, array $options)
            {
                parent::__construct([
                    'key' => $accessKeyId,
                    'secret' => $accessKeySecret,
                    'endpoint' => $endpoint,
                ] + $options);

                $cloned = clone $this;

                $handlerRef = new \ReflectionMethod($cloned, 'chooseHandler');
                /** @var callable */
                $handler = $handlerRef->invoke($cloned, $cloned);

                $this->httpClient = new Client([
                    'timeout' => $this->timeout,
                    'read_timeout' => $this->socketTimeout,
                    'connect_timeout' => $this->connectTimeout,
                    'allow_redirects' => false,
                    'verify' => $this->sslVerify,
                    'expect' => false,
                    'handler' => HandlerStack::create($handler),
                    'curl' => [
                        \CURLOPT_BUFFERSIZE => $this->chunkSize,
                    ],
                ]);
            }
        };

        parent::__construct($options[self::PREFIX_DIR] ?? null);
    }

    public function doSave(\SplFileInfo $originFile, string $targetFileToSave): string
    {
        $result = $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $targetFileToSave,
            'Body' => $originFile->openFile(),
        ]);

        $url = $result['ObjectURL'] ?? null;
        if ($url && \is_string($url)) {
            return $url;
        }

        throw new \LogicException('Invalid response.');
    }

    public function doDelete(string $path): void
    {
        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => ltrim($path, '/')]);
    }
}
