<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Exception;

use Siganushka\MediaBundle\ChannelRegistry;

class UnsupportedChannelException extends \RuntimeException
{
    public function __construct(ChannelRegistry $registry, string $alias)
    {
        parent::__construct(\sprintf('The channel with value "%s" is invalid. Accepted values are: "%s".', $alias, implode('", "', $registry->aliases())));
    }
}
