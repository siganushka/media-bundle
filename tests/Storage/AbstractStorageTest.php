<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Tests\Fixtures\TestStorage;

class AbstractStorageTest extends TestCase
{
    public function testSaveWithoutPrefixDir(): void
    {
        $storage = new TestStorage();

        static::assertSame('/foo.jpg', $storage->save('/tmp/foo.jpg'));
        static::assertSame([['/tmp/foo.jpg', '/foo.jpg']], $storage->saved);
    }

    public function testSaveWithPrefixDir(): void
    {
        $storage = new TestStorage('uploads');

        static::assertSame('/uploads/foo.jpg', $storage->save('/tmp/foo.jpg'));
        static::assertSame('/uploads/foo/bar.jpg', $storage->save(new \SplFileInfo('/tmp/foo.jpg'), 'foo/bar.jpg'));
        static::assertSame(['/uploads/foo.jpg', '/uploads/foo/bar.jpg'], array_column($storage->saved, 1));
    }

    public function testDelete(): void
    {
        $storage = new TestStorage();

        $storage->delete('https://example.com/foo/bar.jpg?hash=test');

        static::assertSame(['/foo/bar.jpg'], $storage->deleted);
    }

    public function testDeleteInvalidUrlException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable parse file.');

        (new TestStorage())->delete('//foo');
    }
}
