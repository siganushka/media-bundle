<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle;

use Siganushka\Contracts\Registry\ServiceRegistry;

/**
 * @method bool                               has(string $serviceId)
 * @method ChannelInterface                   get(string $serviceId)
 * @method array<string,    ChannelInterface> all()
 * @method array<int,       string>           getServiceIds()
 */
class ChannelRegistry extends ServiceRegistry
{
    public function __construct(iterable $channels = [])
    {
        parent::__construct(ChannelInterface::class, $channels);
    }
}
