<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\NamingStrategy;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;

class NamingStrategyTest extends TestCase
{
    #[DataProvider('namingStrategyProvider')]
    public function testAll(string $targetFile, string $namingStrategy): void
    {
        $placeholders = [
            '[timestamp]' => 'test111',
            '[uniqid]' => 'test222',
            '[random]' => 'test333',
            '[custom]' => 'test444',
        ];

        $registry = $this->createMock(RuleRegistry::class);
        $naming = new NamingStrategy($registry, $namingStrategy, $placeholders);

        $rule1 = new Rule('foo');
        $rule2 = new Rule('foo', namingStrategy: $namingStrategy);

        $file = './tests/Fixtures/php.jpg';

        static::assertSame($targetFile, $naming->getTargetFile($rule1, $file));
        static::assertSame($targetFile, $naming->getTargetFile($rule2, $file));
    }

    public static function namingStrategyProvider(): iterable
    {
        yield ['te/st/333.jpg', NamingStrategy::DEFAULT_NAMING];
        yield [\sprintf('%s.jpg', date('y')), '[yy].[ext]'];
        yield [\sprintf('%s.jpg', date('Y')), '[yyyy].[ext]'];
        yield [\sprintf('%s.jpg', date('n')), '[m].[ext]'];
        yield [\sprintf('%s.jpg', date('m')), '[mm].[ext]'];
        yield [\sprintf('%s.jpg', date('j')), '[d].[ext]'];
        yield [\sprintf('%s.jpg', date('d')), '[dd].[ext]'];
        yield ['test111.jpg', '[timestamp].[ext]'];
        yield ['test222.jpg', '[uniqid].[ext]'];
        yield ['test333.jpg', '[random].[ext]'];
        yield ['foo.jpg', '[rule].[ext]'];
        yield ['php.jpg', '[original_name_with_ext]'];
        yield ['[invalid]', '[invalid]'];
        yield ['test444', '[custom]'];
    }
}
