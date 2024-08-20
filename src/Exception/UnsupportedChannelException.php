<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Exception;

use Siganushka\Contracts\Registry\Exception\ServiceRegistryException;
use Siganushka\Contracts\Registry\ServiceRegistryInterface;

class UnsupportedChannelException extends ServiceRegistryException
{
    public function __construct(ServiceRegistryInterface $registry, string $channel)
    {
        parent::__construct($registry, \sprintf('The channel with value "%s" is invalid. Accepted values are: "%s".', $channel, implode('", "', $registry->getServiceIds())));
    }
}
