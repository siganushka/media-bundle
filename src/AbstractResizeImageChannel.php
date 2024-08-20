<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\GenericBundle\Event\ResizeImageEvent;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @see https://symfony.com/doc/current/service_container/service_subscribers_locators.html#service-subscriber-trait
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class AbstractResizeImageChannel extends AbstractChannel implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;

    public function __construct(protected readonly ?int $maxWidth, protected readonly ?int $maxHeight)
    {
    }

    public function onPreSave(File $file): void
    {
        $this->dispatcher()->dispatch(new ResizeImageEvent($file, $this->maxWidth, $this->maxHeight));
    }

    #[SubscribedService]
    public function dispatcher(): EventDispatcherInterface
    {
        return $this->container->get(__METHOD__);
    }
}
