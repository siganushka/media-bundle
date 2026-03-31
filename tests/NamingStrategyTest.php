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
        $file = './tests/Fixtures/php.jpg';

        $registry = $this->createMock(RuleRegistry::class);

        $naming1 = new NamingStrategy($registry);
        $naming2 = new NamingStrategy($registry, $namingStrategy);

        $rule1 = new Rule('foo', namingStrategy: $namingStrategy);
        $rule2 = new Rule('foo');

        static::assertSame($targetFile, $naming1->getTargetFile($rule1, $file));
        static::assertSame($targetFile, $naming2->getTargetFile($rule2, $file));
    }

    public static function namingStrategyProvider(): iterable
    {
        yield ['7e/0bd17a39cf13a.jpg', NamingStrategy::DEFAULT_NAMING];
        yield [\sprintf('%s.jpg', date('y')), '[yy].[ext]'];
        yield [\sprintf('%s.jpg', date('Y')), '[yyyy].[ext]'];
        yield [\sprintf('%s.jpg', date('n')), '[m].[ext]'];
        yield [\sprintf('%s.jpg', date('m')), '[mm].[ext]'];
        yield [\sprintf('%s.jpg', date('j')), '[d].[ext]'];
        yield [\sprintf('%s.jpg', date('d')), '[dd].[ext]'];
        yield [\sprintf('%s.jpg', time()), '[timestamp].[ext]'];
        yield ['7e0bd17a39cf13a8db65d27fdc2de64c.jpg', '[hash].[ext]'];
        yield ['foo.jpg', '[rule].[ext]'];
        yield ['php.jpg', '[original_name_with_ext]'];
        yield ['[invalid_placeholder]', '[invalid_placeholder]'];
    }
}
