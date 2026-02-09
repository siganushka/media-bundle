<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Storage;

use Obs\ObsClient;

/**
 * @see https://support.huaweicloud.com/obs/index.html
 */
class HuaweiObsStorage extends AbstractStorage
{
    public const CNAME = 'is_cname';

    public readonly ObsClient $client;
    public readonly bool $cname;

    public function __construct(string $accessKeyId, string $accessKeySecret, public readonly string $endpoint, public readonly string $bucket, array $options = [])
    {
        if (!class_exists(ObsClient::class)) {
            throw new \LogicException(\sprintf('The "%s" class requires the "obs/esdk-obs-php" component. Try running "composer require obs/esdk-obs-php".', self::class));
        }

        $this->cname = $options[self::CNAME] ?? false;
        $this->client = new ObsClient([
            'key' => $accessKeyId,
            'secret' => $accessKeySecret,
            'endpoint' => $endpoint,
        ] + $options);

        parent::__construct($options[self::PREFIX_DIR] ?? null);
    }

    public function doSave(\SplFileInfo $originFile, string $targetFile): string
    {
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => self::normalize($targetFile),
            'SourceFile' => $originFile->getPathname(),
        ]);

        return $this->buildUrl($targetFile);
    }

    public function doDelete(string $path): void
    {
        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => self::normalize($path)]);
    }

    public function buildUrl(string $path): string
    {
        $result = parse_url($this->endpoint);

        $scheme = $result['scheme'] ?? 'https';
        $domain = $result['host'] ?? $this->endpoint;

        return $this->cname
            ? \sprintf('%s://%s/%s', $scheme, $domain, self::normalize($path))
            : \sprintf('%s://%s.%s/%s', $scheme, $this->bucket, $domain, self::normalize($path));
    }

    public static function normalize(string $key): string
    {
        return ltrim($key, '/\\');
    }
}
