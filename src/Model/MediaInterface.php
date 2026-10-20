<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Model;

use Siganushka\Contracts\Doctrine\CreatableInterface;

interface MediaInterface extends CreatableInterface, \Stringable
{
    public function getHash(): ?string;

    public function setHash(string $hash): static;

    public function getUrl(): ?string;

    public function setUrl(string $url): static;

    public function getName(): ?string;

    public function setName(string $name): static;

    public function getExtension(): ?string;

    public function setExtension(string $extension): static;

    public function getMime(): ?string;

    public function setMime(string $mime): static;

    public function getBytes(): ?int;

    public function setBytes(int $bytes): static;

    public function getSize(): ?string;

    public function getWidth(): ?int;

    public function setWidth(?int $width): static;

    public function getHeight(): ?int;

    public function setHeight(?int $height): static;
}
