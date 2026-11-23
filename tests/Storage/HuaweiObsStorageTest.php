<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Storage\HuaweiObsStorage;

class HuaweiObsStorageTest extends TestCase
{
    public function testBuildUrl(): void
    {
        $storage = new HuaweiObsStorage('access_key_id', 'access_key_secret', 'https://obs.cn-north-4.myhuaweicloud.com', 'bucket');

        static::assertSame('https://bucket.obs.cn-north-4.myhuaweicloud.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testBuildUrlWithCname(): void
    {
        $storage = new HuaweiObsStorage('access_key_id', 'access_key_secret', 'https://obs.cn-north-4.myhuaweicloud.com', 'bucket', [
            HuaweiObsStorage::CNAME => true,
        ]);

        static::assertTrue($storage->cname);
        static::assertSame('https://obs.cn-north-4.myhuaweicloud.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testBuildUrlWithEndpointWithoutScheme(): void
    {
        $storage = new HuaweiObsStorage('access_key_id', 'access_key_secret', 'obs.cn-north-4.myhuaweicloud.com', 'bucket');

        static::assertSame('https://bucket.obs.cn-north-4.myhuaweicloud.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testNormalize(): void
    {
        static::assertSame('foo/bar.jpg', HuaweiObsStorage::normalize('/foo/bar.jpg'));
        static::assertSame('foo/bar.jpg', HuaweiObsStorage::normalize('\\foo/bar.jpg'));
        static::assertSame('', HuaweiObsStorage::normalize(''));
    }
}
