<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\DependencyInjection\SiganushkaMediaExtension;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class SiganushkaMediaExtensionTest extends TestCase
{
    public function testWithDefaultConfig(): void
    {
        $container = $this->createContainerWithConfig([]);

        static::assertSame(__DIR__.'/public', $container->getParameter('siganushka_media.storage_dir'));

        static::assertSame(Media::class, $container->findDefinition(MediaRepository::class)->getArgument('$entityClass'));

        static::assertTrue($container->hasAlias(StorageInterface::class));
    }

    public function testWithCustomConfig(): void
    {
        $config = [
            'media_class' => TestMedia::class,
        ];

        $container = $this->createContainerWithConfig($config);

        static::assertSame(TestMedia::class, $container->findDefinition(MediaRepository::class)->getArgument('$entityClass'));
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
