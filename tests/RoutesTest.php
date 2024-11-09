<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;

class RoutesTest extends TestCase
{
    /**
     * @dataProvider routesProvider
     */
    public function testRotues(string $routeName, string $path, array $methods): void
    {
        $locator = new FileLocator(__DIR__.'/../config/');

        new LoaderResolver([
            $loader = new PhpFileLoader($locator),
            new AttributeDirectoryLoader($locator, new AttributeRouteControllerLoader()),
        ]);

        $routes = $loader->load('routes.php');
        /** @var Route */
        $route = $routes->get($routeName);

        static::assertSame($path, $route->getPath());
        static::assertSame($methods, $route->getMethods());
    }

    public static function routesProvider(): iterable
    {
        yield ['siganushka_media_media_getcollection', '/media', ['GET']];
        yield ['siganushka_media_media_postcollection', '/media', ['POST']];
        yield ['siganushka_media_media_getitem', '/media/{hash}', ['GET']];
        yield ['siganushka_media_media_deleteitem', '/media/{hash}', ['DELETE']];
    }
}
