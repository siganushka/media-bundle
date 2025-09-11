<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Form\Extension;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Form\Extension\RuleTypeExtension;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;

class RuleTypeExtensionTest extends TestCase
{
    public function testGetAccept(): void
    {
        $file = new File();
        static::assertSame('*', RuleTypeExtension::getAcceptFromFile($file));

        $file = new File(mimeTypes: 'application/xml');
        static::assertSame('application/xml', RuleTypeExtension::getAcceptFromFile($file));

        $file = new File(mimeTypes: 'application/json', extensions: [
            'jpg',
            'txt' => 'text/plain',
            'xml' => ['text/xml', 'application/xml'],
        ]);
        static::assertSame('application/json,.jpg,text/plain,text/xml,application/xml', RuleTypeExtension::getAcceptFromFile($file));

        $file = new Image();
        static::assertSame('image/*', RuleTypeExtension::getAcceptFromFile($file));

        $file = new Image(mimeTypes: ['image/jpg', 'image/png']);
        static::assertSame('image/jpg,image/png', RuleTypeExtension::getAcceptFromFile($file));
    }
}
