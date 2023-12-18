<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Siganushka\Contracts\Doctrine\ResourceInterface;
use Siganushka\Contracts\Doctrine\ResourceTrait;
use Siganushka\Contracts\Doctrine\TimestampableInterface;
use Siganushka\Contracts\Doctrine\TimestampableTrait;
use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Repository\MediaRepository;

/**
 * @ORM\Entity(repositoryClass=MediaRepository::class)
 */
class Media implements ResourceInterface, TimestampableInterface
{
    use ResourceTrait;
    use TimestampableTrait;

    public const REFERENCE_FIELD = 'id';
    public const REFERENCE_QUERY = 'ref';

    /**
     * @ORM\Column(type="string", length=32, unique=true, options={"fixed": true})
     */
    private ?string $hash = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $channel = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $url = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $size = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $width = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $height = null;

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @param string|ChannelInterface $channel
     */
    public function isChannel($channel): bool
    {
        if ($channel instanceof ChannelInterface) {
            $channel = $channel->getAlias();
        }

        return $this->channel && $this->channel === $channel;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function isImage(): bool
    {
        return $this->width && $this->height;
    }

    public function getReference(): ?string
    {
        if (null === $this->url) {
            return $this->url;
        }

        return $this->url.'?'.http_build_query([self::REFERENCE_QUERY => $this->{self::REFERENCE_FIELD}]);
    }
}
