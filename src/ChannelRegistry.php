<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Exception\UnsupportedChannelException;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ChannelRegistry
{
    /**
     * @param ServiceLocator<Channel> $locator
     */
    public function __construct(private readonly ServiceLocator $locator)
    {
    }

    public function all(): array
    {
        return iterator_to_array($this->locator);
    }

    public function get(string $alias): Channel
    {
        try {
            return $this->locator->get($alias);
        } catch (\Throwable) {
            throw new UnsupportedChannelException($this, $alias);
        }
    }

    public function aliases(): array
    {
        return array_keys($this->locator->getProvidedServices());
    }
}
