<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    private ChannelInterface $channel;
    private File $file;

    private ?Media $media = null;

    final public function __construct(ChannelInterface $channel, File $file)
    {
        $this->channel = $channel;
        $this->file = $file;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Create event from file path.
     *
     * @return static
     */
    public static function createFromPath(ChannelInterface $channel, string $path): self
    {
        return new static($channel, new File($path));
    }

    /**
     * Create event from remote file url.
     */
    public static function createFromUrl(ChannelInterface $channel, string $url): self
    {
        $content = file_get_contents($url);
        if (false === $content) {
            throw new \RuntimeException('Unable to save file.');
        }

        return self::createFromContent($channel, $content, pathinfo($url, \PATHINFO_BASENAME));
    }

    /**
     * Create event from base64 file content.
     */
    public static function createFromBase64(ChannelInterface $channel, string $base64, string $fileName = null): self
    {
        [$_, $content] = array_pad(explode(',', $base64), 3, null);
        if (null === $content) {
            throw new \InvalidArgumentException('Invalid file base64 data.');
        }

        return self::createFromContent($channel, base64_decode($content), $fileName);
    }

    /**
     * Create event from binary file content.
     */
    public static function createFromContent(ChannelInterface $channel, string $content, string $fileName = null): self
    {
        $file = sprintf('%s/%s', sys_get_temp_dir(), $fileName ?? uniqid());

        file_put_contents($file, $content);
        if (!is_file($file)) {
            throw new \RuntimeException('Unable to save file.');
        }

        return self::createFromPath($channel, $file);
    }
}
