<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Controller\MediaController;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Form\Extension\ChannelTypeExtension;
use Siganushka\MediaBundle\Form\Type\MediaChannelType;
use Siganushka\MediaBundle\Form\Type\MediaFileType;
use Siganushka\MediaBundle\Form\Type\MediaType;
use Siganushka\MediaBundle\Form\Type\MediaUrlType;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Storage\FilesystemStorage;

return static function (ContainerConfigurator $configurator): void {
    $configurator->services()
        ->set(MediaController::class)
            ->autoconfigure()
            ->autowire()
            ->tag('controller.service_arguments')

        ->set('siganushka_media.repository.media', MediaRepository::class)
            ->arg(0, service('doctrine'))
            ->arg(1, Media::class)
            ->tag('doctrine.repository_service')
            ->alias(MediaRepository::class, 'siganushka_media.repository.media')

        ->set('siganushka_media.form.type_extension.channel', ChannelTypeExtension::class)
            ->arg(0, service('siganushka_media.channel_registry'))
            ->tag('form.type_extension')

        ->set('siganushka_media.form.type.media_channel', MediaChannelType::class)
            ->arg(0, service('siganushka_media.channel_registry'))
            ->tag('form.type')

        ->set('siganushka_media.form.type.media_file', MediaFileType::class)
            ->tag('form.type')

        ->set('siganushka_media.form.type.media', MediaType::class)
            ->tag('form.type')

        ->set('siganushka_media.form.type.media_url', MediaUrlType::class)
            ->arg(0, service('siganushka_media.repository.media'))
            ->tag('form.type')

        ->set('siganushka_media.channel_registry', ChannelRegistry::class)
            ->arg(0, tagged_iterator('siganushka_media.channel'))
            ->alias(ChannelRegistry::class, 'siganushka_media.channel_registry')

        ->set('siganushka_media.storage.filesystem', FilesystemStorage::class)
            ->arg(0, service('url_helper'))
            ->arg(1, param('kernel.project_dir').'/public')
            ->alias(FilesystemStorage::class, 'siganushka_media.storage.filesystem')
    ;
};
