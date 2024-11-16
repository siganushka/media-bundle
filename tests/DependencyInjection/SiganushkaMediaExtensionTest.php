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
        $container = new ContainerBuilder(new EnvPlaceholderParameterBag(['kernel.project_dir' => __DIR__]));
        $container->registerExtension(new SiganushkaMediaExtension());
        $container->loadFromExtension('siganushka_media', $config);

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
