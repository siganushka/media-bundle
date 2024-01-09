<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Siganushka\Contracts\Doctrine\ResourceInterface;
use Siganushka\Contracts\Doctrine\ResourceTrait;
use Siganushka\Contracts\Doctrine\TimestampableInterface;
use Siganushka\Contracts\Doctrine\TimestampableTrait;
use Siganushka\MediaBundle\Repository\MediaRepository;

use function Symfony\Component\String\u;

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
     * @ORM\Column(type="string")
     */
    private ?string $hash = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $url = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $extension = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $mimeType = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $bytes = null;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getBytes(): ?int
    {
        return $this->bytes;
    }

    public function setBytes(?int $bytes): self
    {
        $this->bytes = $bytes;

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
        return u($this->mimeType)->startsWith('image');
    }
}
