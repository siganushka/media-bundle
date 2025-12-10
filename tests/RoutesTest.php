<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Controller\MediaController;
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
        $routes = iterator_to_array(self::routesProvider());
        $routeNames = array_map(fn (array $route) => $route[0], $routes);

        static::assertSame($routeNames, array_keys($this->routes->all()));
    }

    #[DataProvider('routesProvider')]
    public function testRotues(string $routeName, string $path, array $methods, array $controller): void
    {
        /** @var Route */
        $route = $this->routes->get($routeName);

        static::assertSame($path, $route->getPath());
        static::assertSame($methods, $route->getMethods());
        static::assertSame($controller, $route->getDefault('_controller'));
    }

    public static function routesProvider(): iterable
    {
        yield ['siganushka_media_getcollection', '/media', ['GET'], [MediaController::class, 'getCollection']];
        yield ['siganushka_media_postcollection', '/media', ['POST'], [MediaController::class, 'postCollection']];
        yield ['siganushka_media_getitem', '/media/{hash}', ['GET'], [MediaController::class, 'getItem']];
        yield ['siganushka_media_deleteitem', '/media/{hash}', ['DELETE'], [MediaController::class, 'deleteItem']];
    }
}
