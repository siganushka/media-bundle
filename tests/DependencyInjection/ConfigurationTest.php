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
            'naming' => '[hash:2]/[hash:13:2].[ext]',
            'rules' => [],
        ]);
    }

    public function testCustomConfig(): void
    {
        $config = [
            'media_class' => FooMedia::class,
            'storage' => AliyunOssStorage::class,
            'naming' => '[yyyy][mm]/[hash].[ext]',
            'rules' => [
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
        static::assertSame($processedConfig['naming'], $config['naming']);

        static::assertSame($processedConfig['rules']['foo']['constraint'], File::class);
        static::assertSame($processedConfig['rules']['foo']['constraint_options'], ['maxSize' => '2MB']);
        static::assertSame($processedConfig['rules']['foo']['resize'], ['enabled' => false, 'max_width' => 1920, 'max_height' => 7680]);
        static::assertSame($processedConfig['rules']['foo']['optimize'], ['enabled' => false, 'quality' => 90]);

        static::assertSame($processedConfig['rules']['bar']['constraint'], Image::class);
        static::assertSame($processedConfig['rules']['bar']['constraint_options'], ['minWidth' => 320, 'allowSquare' => true]);
        static::assertSame($processedConfig['rules']['bar']['resize'], ['enabled' => false, 'max_width' => 1920, 'max_height' => 7680]);
        static::assertSame($processedConfig['rules']['bar']['optimize'], ['enabled' => false, 'quality' => 90]);

        $config['rules']['bar']['resize'] = true;
        $config['rules']['bar']['optimize'] = true;
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['rules']['bar']['resize'], ['enabled' => true, 'max_width' => 1920, 'max_height' => 7680]);
        static::assertEquals($processedConfig['rules']['bar']['optimize'], ['enabled' => true, 'quality' => 90]);

        $config['rules']['bar']['resize'] = 500;
        $config['rules']['bar']['optimize'] = 75;
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['rules']['bar']['resize'], ['enabled' => true, 'max_width' => 500, 'max_height' => 500]);
        static::assertEquals($processedConfig['rules']['bar']['optimize'], ['enabled' => true, 'quality' => 75]);

        $config['rules']['bar']['resize'] = ['max_width' => 300];
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['rules']['bar']['resize'], ['enabled' => true, 'max_width' => 300, 'max_height' => 7680]);

        $config['rules']['bar']['resize'] = ['max_height' => 900];
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);
        static::assertEquals($processedConfig['rules']['bar']['resize'], ['enabled' => true, 'max_width' => 1920, 'max_height' => 900]);

        $config['rules']['bar']['resize'] = ['max_width' => 300, 'max_height' => 900];
        $processedConfig = $processor->processConfiguration(new Configuration(), [$config]);

        static::assertEquals($processedConfig['rules']['bar']['resize'], ['enabled' => true, 'max_width' => 300, 'max_height' => 900]);
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

    public function testRuleConstraintInvalidException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(\sprintf('The value must be instanceof %s, "stdClass" given', File::class));

        $config = [
            'rules' => [
                'foo' => [
                    'constraint' => \stdClass::class,
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [$config]);
    }

    public function testRuleOptimizeInvalidException(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value 120 is too big for path "siganushka_media.rules.foo.optimize.quality". Should be less than or equal to 100');

        $config = [
            'rules' => [
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
