<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Symfony\Component\DependencyInjection\ServiceLocator;

class RuleRegistryTest extends TestCase
{
    public function testAll(): void
    {
        /** @var ServiceLocator<Rule> $locator */
        $locator = new ServiceLocator([
            'foo' => static fn () => new Rule('foo'),
            'bar' => static fn () => new Rule('bar'),
        ]);

        $registry = new RuleRegistry($locator);

        static::assertSame(['foo', 'bar'], $registry->aliases());
        static::assertSame('foo', $registry->get('foo')->alias);
        static::assertSame('bar', $registry->get('bar')->alias);

        $rules = $registry->all();
        static::assertSame(['foo', 'bar'], array_keys($rules));
        static::assertInstanceOf(Rule::class, $rules['foo']);
        static::assertSame('foo', $rules['foo']->alias);
    }
}
