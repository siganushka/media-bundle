<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Storage\AliyunOssStorage;

class AliyunOssStorageTest extends TestCase
{
    public function testBuildUrl(): void
    {
        $storage = new AliyunOssStorage('access_key_id', 'access_key_secret', 'https://oss-cn-hangzhou.aliyuncs.com', 'bucket');

        static::assertSame('https://bucket.oss-cn-hangzhou.aliyuncs.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testBuildUrlWithCname(): void
    {
        $storage = new AliyunOssStorage('access_key_id', 'access_key_secret', 'https://oss-cn-hangzhou.aliyuncs.com', 'bucket', [
            AliyunOssStorage::CNAME => true,
        ]);

        static::assertTrue($storage->cname);
        static::assertSame('https://oss-cn-hangzhou.aliyuncs.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testBuildUrlWithoutSsl(): void
    {
        $storage = new AliyunOssStorage('access_key_id', 'access_key_secret', 'https://oss-cn-hangzhou.aliyuncs.com', 'bucket', [
            AliyunOssStorage::USE_SSL => false,
        ]);

        static::assertSame('http://bucket.oss-cn-hangzhou.aliyuncs.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testBuildUrlWithEndpointWithoutScheme(): void
    {
        $storage = new AliyunOssStorage('access_key_id', 'access_key_secret', 'oss-cn-hangzhou.aliyuncs.com', 'bucket');

        static::assertSame('https://bucket.oss-cn-hangzhou.aliyuncs.com/foo/bar.jpg', $storage->buildUrl('/foo/bar.jpg'));
    }

    public function testNormalize(): void
    {
        static::assertSame('foo/bar.jpg', AliyunOssStorage::normalize('/foo/bar.jpg'));
        static::assertSame('foo/bar.jpg', AliyunOssStorage::normalize('\\foo/bar.jpg'));
        static::assertSame('', AliyunOssStorage::normalize(''));
    }
}
