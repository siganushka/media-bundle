<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\NamingStrategy;
use Siganushka\MediaBundle\Rule;

class NamingStrategyTest extends TestCase
{
    #[DataProvider('namingStrategyProvider')]
    public function testAll(string $targetFile, string $namingStrategy): void
    {
        $file = './tests/Fixtures/php.jpg';

        $placeholders = [
            '[foo]' => 'test111',
            '[timestamp]' => 'test222',
            '[uniqid]' => 'test333',
        ];

        $naming1 = new NamingStrategy($namingStrategy, $placeholders);
        $naming2 = new NamingStrategy($namingStrategy, $placeholders);

        $rule1 = new Rule('foo', namingStrategy: $namingStrategy);
        $rule2 = new Rule('foo');

        static::assertSame($targetFile, $naming1->getTargetFile(new MediaSaveEvent($rule1, $file)));
        static::assertSame($targetFile, $naming2->getTargetFile(new MediaSaveEvent($rule2, $file)));
    }

    public static function namingStrategyProvider(): iterable
    {
        yield ['7e/0b/d17a39cf13a8.jpg', NamingStrategy::DEFAULT_NAMING];
        yield [\sprintf('%s.jpg', date('y')), '[yy].[ext]'];
        yield [\sprintf('%s.jpg', date('Y')), '[yyyy].[ext]'];
        yield [\sprintf('%s.jpg', date('n')), '[m].[ext]'];
        yield [\sprintf('%s.jpg', date('m')), '[mm].[ext]'];
        yield [\sprintf('%s.jpg', date('j')), '[d].[ext]'];
        yield [\sprintf('%s.jpg', date('d')), '[dd].[ext]'];
        yield ['test222.jpg', '[timestamp].[ext]'];
        yield ['test333.jpg', '[uniqid].[ext]'];
        yield ['7e0bd17a39cf13a8db65d27fdc2de64c.jpg', '[hash].[ext]'];
        yield ['foo.jpg', '[rule].[ext]'];
        yield ['php.jpg', '[original_name_with_ext]'];
        yield ['[invalid_placeholder]', '[invalid_placeholder]'];
        yield ['test111', '[foo]'];
    }
}
