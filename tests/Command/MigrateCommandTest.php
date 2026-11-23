<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Siganushka\MediaBundle\Command\MigrateCommand;
use Siganushka\MediaBundle\MediaManagerInterface;
use Siganushka\MediaBundle\RuleRegistry;
use Siganushka\MediaBundle\Tests\Fixtures\TestMedia;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class MigrateCommandTest extends TestCase
{
    public function testGetEntities(): void
    {
        $normal = new ClassMetadata(\stdClass::class);
        $sorted = new ClassMetadata(\ArrayObject::class);

        $mappedSuperclass = new ClassMetadata(\SplStack::class);
        $mappedSuperclass->isMappedSuperclass = true;

        $embedded = new ClassMetadata(\SplQueue::class);
        $embedded->isEmbeddedClass = true;

        $media = new ClassMetadata(TestMedia::class);

        $factory = $this->createMock(ClassMetadataFactory::class);
        $factory->method('getAllMetadata')->willReturn([$normal, $mappedSuperclass, $embedded, $media, $sorted]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getMetadataFactory')->willReturn($factory);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn([$entityManager]);

        /** @var array<string, array<int, string>> $entities */
        $entities = $this->invoke($this->createCommand($registry), 'getEntities');

        static::assertSame([\ArrayObject::class, \stdClass::class], array_keys($entities));
    }

    public function testGetArgumentForAskReturnsProvidedArgument(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(static::once())
            ->method('getArgument')
            ->with('rule-alias')
            ->willReturn('foo')
        ;

        static::assertSame('foo', $this->invoke(
            $this->createCommand(),
            'getArgumentForAsk',
            'rule-alias',
            $input,
            $this->createMock(OutputInterface::class),
            ['foo', 'bar']
        ));
    }

    public function testCreateFileFromStorageDir(): void
    {
        $storageDir = \sprintf('%s/media_migrate_%s', sys_get_temp_dir(), uniqid());
        mkdir($storageDir.'/images', 0o777, true);
        file_put_contents($storageDir.'/images/foo.jpg', 'test content');

        $target = $this->invoke($this->createCommand(storageDir: $storageDir), 'createFile', 'images/foo.jpg');

        static::assertIsString($target);
        static::assertSame('foo.jpg', pathinfo($target, \PATHINFO_BASENAME));
        static::assertSame('test content', file_get_contents($target));

        @unlink($target);
        (new Filesystem())->remove($storageDir);
    }

    public function testCreateFileWithMissingFileException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid file like');

        $this->invoke($this->createCommand(), 'createFile', 'not-exists.jpg');
    }

    private function createCommand(?ManagerRegistry $registry = null, ?string $storageDir = null): MigrateCommand
    {
        return new MigrateCommand(
            $registry ?? $this->createMock(ManagerRegistry::class),
            $this->createMock(RuleRegistry::class),
            $this->createMock(MediaManagerInterface::class),
            $storageDir ?? sys_get_temp_dir(),
        );
    }

    private function invoke(MigrateCommand $command, string $method, mixed ...$arguments): mixed
    {
        return (new \ReflectionMethod($command, $method))->invoke($command, ...$arguments);
    }
}
