<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\DependencyInjection\SiganushkaMediaExtension;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class SiganushkaMediaExtensionTest extends TestCase
{
    public function testLoadDefaultConfig(): void
    {
        $container = $this->createContainerWithConfig([]);

        static::assertTrue($container->hasAlias(StorageInterface::class));
    }

    private function createContainerWithConfig(array $config = []): ContainerBuilder
    {
        $parameters = [
            'kernel.project_dir' => __DIR__,
        ];

        $extension = new SiganushkaMediaExtension();

        $container = new ContainerBuilder(new EnvPlaceholderParameterBag($parameters));
        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias(), $config);

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
