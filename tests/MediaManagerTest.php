<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests;

use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\MediaManager;
use Siganushka\MediaBundle\Rule;
use Siganushka\MediaBundle\RuleRegistry;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MediaManagerTest extends TestCase
{
    public function testSaveWithStringRule(): void
    {
        $rule = new Rule('foo');
        $media = new TestMedia();

        $registry = $this->createMock(RuleRegistry::class);
        $registry->expects(static::once())
            ->method('get')
            ->with('foo')
            ->willReturn($rule)
        ;

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(MediaSaveEvent::class, static function (MediaSaveEvent $event) use ($media): void {
            $event->setMedia($media);
        });

        $manager = new MediaManager($registry, $dispatcher);

        static::assertSame($media, $manager->save('foo', './tests/Fixtures/php.jpg'));
    }

    public function testSaveWithRuleInstance(): void
    {
        $media = new TestMedia();

        $registry = $this->createMock(RuleRegistry::class);
        $registry->expects(static::never())->method('get');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(MediaSaveEvent::class, static function (MediaSaveEvent $event) use ($media): void {
            $event->setMedia($media);
        });

        $manager = new MediaManager($registry, $dispatcher);

        static::assertSame($media, $manager->save(new Rule('foo'), './tests/Fixtures/php.jpg'));
    }

    public function testSaveWithoutMediaException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to save file.');

        $manager = new MediaManager($this->createMock(RuleRegistry::class), new EventDispatcher());
        $manager->save(new Rule('foo'), './tests/Fixtures/php.jpg');
    }
}
