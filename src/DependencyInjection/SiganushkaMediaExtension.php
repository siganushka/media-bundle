<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\DependencyInjection;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Doctrine\Types\MediaUrlType;
use Siganushka\MediaBundle\Storage\StorageInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SiganushkaMediaExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setAlias(StorageInterface::class, $config['storage']);

        $container->registerForAutoconfiguration(ChannelInterface::class)
            ->addTag('siganushka_media.channel')
        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('doctrine')) {
            return;
        }

        $container->prependExtensionConfig('doctrine', [
            'dbal' => [
                'types' => [
                    'media' => MediaUrlType::class,
                ],
            ],
        ]);
    }
}
