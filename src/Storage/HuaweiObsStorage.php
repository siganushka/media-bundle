<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Obs\ObsClient;

/**
 * @see https://support.huaweicloud.com/obs/index.html
 */
class HuaweiObsStorage extends AbstractStorage
{
    public readonly ObsClient $client;

    public function __construct(string $accessKeyId, string $accessKeySecret, string $endpoint, private readonly string $bucket, array $options = [])
    {
        if (!class_exists(ObsClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "obs/esdk-obs-php" component. Try running "composer require obs/esdk-obs-php".', self::class));
        }

        $this->client = new ObsClient([
            'key' => $accessKeyId,
            'secret' => $accessKeySecret,
            'endpoint' => $endpoint,
        ] + $options);

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
