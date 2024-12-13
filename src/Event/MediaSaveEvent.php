<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Event;

use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\EventDispatcher\Event;

class MediaSaveEvent extends Event
{
    private ?Media $media = null;

    final public function __construct(private readonly ChannelInterface $channel, private readonly File $file)
    {
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

    public function setMedia(?Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Create from file path.
     *
     * @return static
     */
    public static function createFromPath(ChannelInterface $channel, string $path): self
    {
        return new static($channel, new File($path));
    }

    /**
     * Create from remote file url.
     *
     * @see https://www.jianshu.com/p/42e0c4304b60
     */
    public static function createFromUrl(ChannelInterface $channel, string $url): self
    {
        if (false === $curl = curl_init()) {
            throw new \RuntimeException('failed to initialize');
        }

        curl_setopt($curl, \CURLOPT_URL, $url);
        curl_setopt($curl, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, \CURLOPT_RETURNTRANSFER, true);

        $content = curl_exec($curl);
        if (!\is_string($content)) {
            throw new \RuntimeException(curl_error($curl));
        }

        // Close curl resource.
        curl_close($curl);

        return self::createFromContent($channel, $content, pathinfo($url, \PATHINFO_BASENAME));
    }

    /**
     * Create from data URI file content.
     *
     * @see https://en.wikipedia.org/wiki/Data_URI_scheme
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URLs
     */
    public static function createFromDataUri(ChannelInterface $channel, string $dataUri, ?string $fileName = null): self
    {
        if (!preg_match('/^data:([a-z0-9][a-z0-9\!\#\$\&\-\^\_\+\.]{0,126}\/[a-z0-9][a-z0-9\!\#\$\&\-\^\_\+\.]{0,126}(;[a-z0-9\-]+\=[a-z0-9\-]+)?)?(;base64)?,[a-z0-9\!\$\&\\\'\,\(\)\*\+\,\;\=\-\.\_\~\:\@\/\?\%\s]*\s*$/i', $dataUri)) {
            throw new \InvalidArgumentException('Invalid data uri file.');
        }

        [$_, $content] = array_pad(explode(',', $dataUri), 3, null);
        if (null === $content) {
            throw new \InvalidArgumentException('Invalid data uri file.');
        }

        return self::createFromContent($channel, base64_decode($content), $fileName);
    }

    /**
     * Create from binary file content.
     */
    public static function createFromContent(ChannelInterface $channel, string $content, ?string $fileName = null): self
    {
        $file = \sprintf('%s/%s', sys_get_temp_dir(), $fileName ?? uniqid());

        file_put_contents($file, $content);
        if (!is_file($file)) {
            throw new \RuntimeException('Unable to save file.');
        }

        return self::createFromPath($channel, $file);
    }
}
