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
 * @ORM\Table(uniqueConstraints={
 *  @ORM\UniqueConstraint(columns={"hash"})
 * })
 */
class Media implements ResourceInterface, TimestampableInterface
{
    use ResourceTrait;
    use TimestampableTrait;

    /**
     * @ORM\Column(type="string", length=32, options={"fixed": true})
     */
    private ?string $hash = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $channel = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $url = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $size = null;

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

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * @param string|ChannelInterface $channel
     */
    public function setChannel($channel): self
    {
        $this->channel = (string) $channel;

        return $this;
    }

    /**
     * @param string|ChannelInterface $channel
     */
    public function isChannel($channel): bool
    {
        if ($channel instanceof ChannelInterface) {
            $channel = (string) $channel;
        }

        return $this->channel && $this->channel === $channel;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): self
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
}
