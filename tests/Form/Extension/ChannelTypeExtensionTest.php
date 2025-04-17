<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Form\Extension;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Form\Extension\ChannelTypeExtension;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class ChannelTypeExtensionTest extends TestCase
{
    public function testGetAccept(): void
    {
        $file = new File();
        static::assertSame('*', ChannelTypeExtension::getAcceptFromFile($file));

        $file = new File(mimeTypes: 'application/xml');
        static::assertSame('application/xml', ChannelTypeExtension::getAcceptFromFile($file));

        $file = new File(mimeTypes: 'application/json', extensions: [
            'jpg',
            'txt' => 'text/plain',
            'xml' => ['text/xml', 'application/xml'],
        ]);
        static::assertSame('application/json,.jpg,text/plain,text/xml,application/xml', ChannelTypeExtension::getAcceptFromFile($file));

        $file = new Image();
        static::assertSame('image/*', ChannelTypeExtension::getAcceptFromFile($file));

        $file = new Image(mimeTypes: ['image/jpg', 'image/png']);
        static::assertSame('image/jpg,image/png', ChannelTypeExtension::getAcceptFromFile($file));
    }
}
