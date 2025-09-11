<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Symfony\Component\DependencyInjection\ServiceLocator;

final class RuleRegistry
{
    /**
     * @param ServiceLocator<Rule> $locator
     */
    public function __construct(private readonly ServiceLocator $locator)
    {
    }

    public function all(): array
    {
        return iterator_to_array($this->locator);
    }

    public function get(string $alias): Rule
    {
        return $this->locator->get($alias);
    }

    public function aliases(): array
    {
        return array_keys($this->locator->getProvidedServices());
    }
}
