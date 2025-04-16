<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Channel;
use Siganushka\MediaBundle\Utils\FileUtils;

class FileUtilsTest extends TestCase
{
    protected Channel $channel;

    protected function setUp(): void
    {
        $this->channel = new Channel('foo');
    }

    public function testCreateFromUrl(): void
    {
        try {
            $file = FileUtils::createFromUrl('https://github.githubassets.com/assets/GitHub-Mark-ea2971cee799.png');

            static::assertSame('GitHub-Mark-ea2971cee799.png', $file->getFilename());
            static::assertNotFalse(@getimagesize($file->getPathname()));

            // Delete file after tests.
            @unlink($file->getPathname());
        } catch (\Throwable $th) {
            $this->markTestSkipped(\sprintf('Fail to download remote file (Skipped with message "%s").', $th->getMessage()));
        }
    }

    public function testCreateFromDataUri(): void
    {
        $dataUri = 'data:image/jpeg;base64,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';

        $file = FileUtils::createFromDataUri($dataUri, 'foo.jpg');
        static::assertSame('foo.jpg', $file->getFilename());
        static::assertNotFalse(@getimagesize($file->getPathname()));

        // Delete file after tests.
        @unlink($file->getPathname());
    }

    public function testCreateFromContent(): void
    {
        $content = file_get_contents('./tests/Fixtures/php.jpg');
        if (false === $content) {
            $this->markTestSkipped('Cannot not access file (Skipped with message "%s").');
        }

        $file = FileUtils::createFromContent($content, 'bar.jpg');
        static::assertSame('bar.jpg', $file->getFilename());
        static::assertNotFalse(@getimagesize($file->getPathname()));

        // Delete file after tests.
        @unlink($file->getPathname());
    }

    public function testDataUriInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data URI file.');

        FileUtils::createFromDataUri('foo');
    }

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
