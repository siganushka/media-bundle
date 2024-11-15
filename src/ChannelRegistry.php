<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\MediaBundle\Exception\UnsupportedChannelException;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function Symfony\Component\String\u;

final class ChannelRegistry
{
    /**
     * @param ServiceLocator<ChannelInterface> $locator
     */
    public function __construct(private readonly ServiceLocator $locator)
    {
    }

    public function all(): array
    {
        return iterator_to_array($this->locator);
    }

    public function get(string $alias): ChannelInterface
    {
        $normalizedAlias = self::normalizeAlias($alias);

        try {
            return $this->locator->get($normalizedAlias);
        } catch (\Throwable) {
            throw new UnsupportedChannelException($this, $alias);
        }
    }

    public function aliases(): array
    {
        return array_keys($this->locator->getProvidedServices());
    }

    public static function normalizeAlias(string $alias): string
    {
        return u($alias)->afterLast('\\')->snake()->toString();
    }
}
