<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Obs\ObsClient;

/**
 * @see https://support.huaweicloud.com/obs/index.html
 */
class HuaweiObsStorage implements StorageInterface
{
    private readonly ObsClient $client;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, private readonly string $bucket)
    {
        if (!class_exists(ObsClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "obs/esdk-obs-php" component. Try running "composer require obs/esdk-obs-php".', self::class));
        }

        $this->client = ObsClient::factory([
            'key' => $accessKeyId,
            'secret' => $accessKeySecret,
            'endpoint' => $endpoint,
            'socket_timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function save(string|\SplFileInfo $originFile, ?string $targetFile = null): string
    {
        if (\is_string($originFile)) {
            $originFile = new \SplFileInfo($originFile);
        }

        $result = $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $targetFile ?? $originFile->getBasename(),
            'Body' => $originFile->openFile(),
        ]);

        if ($originFile->isFile()) {
            @unlink($originFile->getPathname());
        }

        $url = $result['ObjectURL'] ?? null;
        if ($url && \is_string($url)) {
            return $url;
        }

        throw new \LogicException('Invalid response.');
    }

    public function delete(string $url): void
    {
        $object = parse_url($url, \PHP_URL_PATH);
        if (!\is_string($object)) {
            throw new \RuntimeException('Unable parse file.');
        }

        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => ltrim($object, '/')]);
    }
}
