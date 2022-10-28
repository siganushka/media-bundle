<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Form\DataTransformer;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Exception\UnsupportedChannelException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Util\FormUtil;

class ChannelToAliasTransformer implements DataTransformerInterface
{
    private ChannelRegistry $registry;

    public function __construct(ChannelRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param mixed $value
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof ChannelInterface) {
            throw new TransformationFailedException(sprintf('Expected a %s.', ChannelInterface::class));
        }

        return $value->getAlias();
    }

    /**
     * @param mixed $value
     */
    public function reverseTransform($value): ?ChannelInterface
    {
        if (FormUtil::isEmpty($value)) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ($this->registry->has($value)) {
            return $this->registry->get($value);
        }

        // find by class name
        foreach ($this->registry->all() as $channel) {
            if ($value === \get_class($channel)) {
                return $channel;
            }
        }

        throw new UnsupportedChannelException($this->registry, $value);
    }
}
