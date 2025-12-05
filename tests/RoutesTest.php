<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutesTest extends TestCase
{
    protected RouteCollection $routes;

    protected function setUp(): void
    {
        $loader = new PhpFileLoader(new FileLocator(__DIR__.'/../config/'));
        $this->routes = $loader->load('routes.php');
    }

    public function testAll(): void
    {
        static::assertSame([
            'siganushka_media_getcollection',
            'siganushka_media_postcollection',
            'siganushka_media_getitem',
            'siganushka_media_deleteitem',
        ], array_keys($this->routes->all()));
    }

    /**
     * @dataProvider routesProvider
     */
    public function testRotues(string $routeName, string $path, array $methods): void
    {
        /** @var Route */
        $route = $this->routes->get($routeName);

        static::assertSame($path, $route->getPath());
        static::assertSame($methods, $route->getMethods());
        static::assertTrue($route->getDefault('_stateless'));
    }

    public static function routesProvider(): iterable
    {
        yield ['siganushka_media_getcollection', '/media', ['GET']];
        yield ['siganushka_media_postcollection', '/media', ['POST']];
        yield ['siganushka_media_getitem', '/media/{hash}', ['GET']];
        yield ['siganushka_media_deleteitem', '/media/{hash}', ['DELETE']];
    }
}
