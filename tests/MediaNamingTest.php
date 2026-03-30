<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\MediaNaming;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;

class MediaNamingTest extends TestCase
{
    public function testAll(): void
    {
        $registry = $this->createMock(RuleRegistry::class);
        $naming = new MediaNaming($registry);

        $file = './tests/Fixtures/php.jpg';

        static::assertSame('7e/0bd17a39cf13a.jpg', $naming->getTargetFile(new Rule('foo'), $file));
        static::assertSame(\sprintf('%s.jpg', date('y')), $naming->getTargetFile(new Rule('foo', namingStrategy: '[yy].[ext]'), $file));
        static::assertSame(\sprintf('%s.jpg', date('Y')), $naming->getTargetFile(new Rule('foo', namingStrategy: '[yyyy].[ext]'), $file));
        static::assertSame(\sprintf('%s.jpg', date('n')), $naming->getTargetFile(new Rule('foo', namingStrategy: '[m].[ext]'), $file));
        static::assertSame(\sprintf('%s.jpg', date('m')), $naming->getTargetFile(new Rule('foo', namingStrategy: '[mm].[ext]'), $file));
        static::assertSame(\sprintf('%s.jpg', date('j')), $naming->getTargetFile(new Rule('foo', namingStrategy: '[d].[ext]'), $file));
        static::assertSame(\sprintf('%s.jpg', date('d')), $naming->getTargetFile(new Rule('foo', namingStrategy: '[dd].[ext]'), $file));
        static::assertSame(\sprintf('%s.jpg', time()), $naming->getTargetFile(new Rule('foo', namingStrategy: '[timestamp].[ext]'), $file));
        static::assertSame('7e0bd17a39cf13a8db65d27fdc2de64c.jpg', $naming->getTargetFile(new Rule('foo', namingStrategy: '[hash].[ext]'), $file));
        static::assertSame('foo.jpg', $naming->getTargetFile(new Rule('foo', namingStrategy: '[rule].[ext]'), $file));
        static::assertSame('php.jpg', $naming->getTargetFile(new Rule('foo', namingStrategy: '[original_name]'), $file));
    }
}
