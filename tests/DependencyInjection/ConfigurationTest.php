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

        static::assertSame($processedConfig['media_class'], $config['media_class']);
        static::assertSame($processedConfig['storage'], $config['storage']);

        static::assertSame($processedConfig['channels']['foo']['constraint'], File::class);
        static::assertSame($processedConfig['channels']['foo']['constraint_options'], ['maxSize' => '2MB']);
        static::assertSame($processedConfig['channels']['foo']['resize'], ['enabled' => false, 'max_width' => 1000, 'max_height' => 9999]);
        static::assertSame($processedConfig['channels']['foo']['optimize'], ['enabled' => false, 'quality' => 85]);

        static::assertSame($processedConfig['channels']['bar']['constraint'], Image::class);
        static::assertSame($processedConfig['channels']['bar']['constraint_options'], ['minWidth' => 320, 'allowSquare' => true]);
        static::assertSame($processedConfig['channels']['bar']['resize'], ['enabled' => false, 'max_width' => 1000, 'max_height' => 9999]);
        static::assertSame($processedConfig['channels']['bar']['optimize'], ['enabled' => false, 'quality' => 85]);

        $config['channels']['bar']['resize'] = true;
        $config['channels']['bar']['optimize'] = true;
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['channels']['bar']['resize'], ['enabled' => true, 'max_width' => 1000, 'max_height' => 9999]);
        static::assertEquals($processedConfig['channels']['bar']['optimize'], ['enabled' => true, 'quality' => 85]);

        $config['channels']['bar']['resize'] = 500;
        $config['channels']['bar']['optimize'] = 75;
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['channels']['bar']['resize'], ['enabled' => true, 'max_width' => 500, 'max_height' => 500]);
        static::assertEquals($processedConfig['channels']['bar']['optimize'], ['enabled' => true, 'quality' => 75]);

        $config['channels']['bar']['resize'] = ['max_width' => 300];
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['channels']['bar']['resize'], ['enabled' => true, 'max_width' => 300, 'max_height' => 9999]);

        $config['channels']['bar']['resize'] = ['max_height' => 900];
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);
        static::assertEquals($processedConfig['channels']['bar']['resize'], ['enabled' => true, 'max_width' => 1000, 'max_height' => 900]);

        $config['channels']['bar']['resize'] = ['max_width' => 300, 'max_height' => 900];
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['channels']['bar']['resize'], ['enabled' => true, 'max_width' => 300, 'max_height' => 900]);
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

    public function testChannelConstraintInvalidException(): void
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

    public function testChannelOptimizeInvalidException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value 120 is too big for path "siganushka_media.channels.foo.optimize.quality". Should be less than or equal to 100');

        $config = [
            'channels' => [
                'foo' => [
                    'optimize' => 120,
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
