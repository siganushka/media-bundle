<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\DependencyInjection\Configuration;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Storage\AliyunOssStorage;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration(), []);

        static::assertSame($processedConfig, [
            'media_class' => Media::class,
            'storage' => LocalStorage::class,
            'channels' => [],
        ]);
    }

    public function testCustomConfig(): void
    {
        $config = [
            'media_class' => FooMedia::class,
            'storage' => AliyunOssStorage::class,
            'channels' => [
                'foo' => [
                    'constraint_options' => ['maxSize' => '2MB'],
                ],
                'bar' => [
                    'constraint' => 'image',
                    'constraint_options' => ['minWidth' => 320, 'allowSquare' => true],
                ],
            ],
        ];

        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);
        // dd($processedConfig);

        static::assertSame($processedConfig['media_class'], $config['media_class']);
        static::assertSame($processedConfig['storage'], $config['storage']);

        static::assertSame($processedConfig['channels']['foo']['constraint'], File::class);
        static::assertSame($processedConfig['channels']['foo']['constraint_options'], ['maxSize' => '2MB']);

        static::assertSame($processedConfig['channels']['bar']['constraint'], Image::class);
        static::assertSame($processedConfig['channels']['bar']['constraint_options'], ['minWidth' => 320, 'allowSquare' => true]);
    }

    public function testMediaClassInvalidException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(\sprintf('The value must be instanceof %s, "stdClass" given', Media::class));

        $config = [
            'media_class' => \stdClass::class,
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [$config]);
    }

    public function testStorageInvalidException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(\sprintf('The value must be instanceof %s, "stdClass" given', StorageInterface::class));

        $config = [
            'storage' => \stdClass::class,
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [$config]);
    }

    public function testChannelsInvalidException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(\sprintf('The value must be instanceof %s, "stdClass" given', File::class));

        $config = [
            'channels' => [
                'foo' => [
                    'constraint' => \stdClass::class,
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [$config]);
    }
}

class FooMedia extends Media
{
}
