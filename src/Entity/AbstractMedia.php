<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Siganushka\Contracts\Doctrine\CreatableTrait;
use Siganushka\MediaBundle\Model\MediaInterface;
use Siganushka\MediaBundle\Repository\MediaRepository;
use Siganushka\MediaBundle\Utils\FileUtils;

#[ORM\MappedSuperclass(repositoryClass: MediaRepository::class)]
#[ORM\UniqueConstraint(columns: ['hash'])]
abstract class AbstractMedia implements MediaInterface
{
    use CreatableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    protected ?int $id = null;

    #[ORM\Column]
    protected ?string $hash = null;

    #[ORM\Column]
    protected ?string $url = null;

    #[ORM\Column]
    protected ?string $name = null;

    #[ORM\Column]
    protected ?string $extension = null;

    #[ORM\Column]
    protected ?string $mime = null;

    #[ORM\Column]
    protected ?int $bytes = null;

    #[ORM\Column]
    protected ?string $size = null;

    #[ORM\Column(nullable: true)]
    protected ?int $width = null;

    #[ORM\Column(nullable: true)]
    protected ?int $height = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(string $mime): static
    {
        $this->mime = $mime;

        return $this;
    }

    public function getBytes(): ?int
    {
        return $this->bytes;
    }

    public function setBytes(int $bytes): static
    {
        $this->bytes = $bytes;
        $this->size = FileUtils::formatBytes($bytes);

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function __toString(): string
    {
        return \sprintf('%s?hash=%s', $this->url, $this->hash);
    }
}
