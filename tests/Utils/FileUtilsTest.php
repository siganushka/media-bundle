<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Utils\FileUtils;

class FileUtilsTest extends TestCase
{
    public function testGetFormattedSize(): void
    {
        static::assertSame('50.01KB', FileUtils::getFormattedSize(new \SplFileInfo('./tests/Fixtures/landscape.jpg')));
    }

    public function testGetFormattedSizeRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);

        FileUtils::getFormattedSize(new \SplFileInfo('./non_existing_file'));
    }

    /**
     * @dataProvider bytesProvider
     */
    public function testFormatBytes(string $formatted, int $bytes): void
    {
        static::assertSame($formatted, FileUtils::formatBytes($bytes));
    }

    public static function bytesProvider(): iterable
    {
        yield ['0B', 0];
        yield ['0B', -1];
        yield ['1B', 1];
        yield ['1023B', 1023];
        yield ['1KB', 1024];
        yield ['64KB', 65535];
        yield ['64MB', 65535 * 1024];
        yield ['2GB', 2147483647];
        yield ['8EB', \PHP_INT_MAX];
    }
}
