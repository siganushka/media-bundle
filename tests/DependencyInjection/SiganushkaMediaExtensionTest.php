<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\DependencyInjection\SiganushkaMediaExtension;
use Siganushka\MediaBundle\Doctrine\MediaListener;
use Siganushka\MediaBundle\Model\MediaInterface;
use Siganushka\MediaBundle\NamingStrategy;
use Siganushka\MediaBundle\RuleRegistry;
use Siganushka\MediaBundle\Storage\LocalStorage;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SiganushkaMediaExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', __DIR__);

        $extension = new SiganushkaMediaExtension();
        $extension->load([['media_class' => TestMedia::class]], $container);

        static::assertSame('%kernel.project_dir%/public', $container->getParameter('siganushka_media.storage_dir'));

        $ruleRegistry = $container->findDefinition(RuleRegistry::class);

        /** @var Reference */
        $locator = $ruleRegistry->getArgument('$locator');
        static::assertStringContainsString('service_locator', $locator->__toString());

        $migrateCommand = $container->findDefinition(MigrateCommand::class);
        static::assertSame('%siganushka_media.storage_dir%', $migrateCommand->getArgument('$storageDir'));

        $localStorage = $container->findDefinition(LocalStorage::class);
        static::assertSame('%siganushka_media.storage_dir%', $localStorage->getArgument('$storageDir'));

        $mediaListener = $container->findDefinition(MediaListener::class);
        static::assertSame([
            ['event' => 'postRemove', 'entity' => TestMedia::class],
        ], $mediaListener->getTag('doctrine.orm.entity_listener'));

        $namingStrategy = $container->findDefinition(NamingStrategy::class);
        static::assertSame(NamingStrategy::DEFAULT_NAMING, $namingStrategy->getArgument('$defaultNamingStrategy'));

        static::assertTrue($container->hasAlias(StorageInterface::class));
    }

    public function testPrepend(): void
    {
        $container = new ContainerBuilder();

        $extension = new SiganushkaMediaExtension();
        $extension->prepend($container);

        static::assertSame([], $container->getExtensionConfig('doctrine'));

        $container->prependExtensionConfig('siganushka_media', ['media_class' => 'foo']);
        $extension->prepend($container);

        static::assertSame([
            [
                'orm' => [
                    'resolve_target_entities' => [MediaInterface::class => 'foo'],
                ],
            ],
        ], $container->getExtensionConfig('doctrine'));
    }
}
