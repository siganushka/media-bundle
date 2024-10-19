<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Siganushka\Contracts\Doctrine\ResourceInterface;
use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'siganushka:media:migrate',
    description: 'Migrate existing media data to SiganushkaMediaBundle.',
)]
class MigrateCommand extends Command
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ManagerRegistry $managerRegistry,
        private readonly ChannelRegistry $channelRegistry,
        private readonly string $publicDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'Which entity do you want to migrate (fully-qualified class name)?')
            ->addArgument('from-field', InputArgument::OPTIONAL, 'Which field do you want to migrate from?')
            ->addArgument('to-field', InputArgument::OPTIONAL, 'Which field do you want to migrate to?')
            ->addArgument('channel-alias', InputArgument::OPTIONAL, 'Which channel to use?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entities = $this->getEntities();

        /** @psalm-var class-string */
        $entityClass = $this->getArgumentForAsk('entity-class', $input, $output, array_keys($entities), false);
        $fromField = $this->getArgumentForAsk('from-field', $input, $output, $entities[$entityClass] ?? []);
        $toField = $this->getArgumentForAsk('to-field', $input, $output, $entities[$entityClass] ?? []);
        $channelAlias = $this->getArgumentForAsk('channel-alias', $input, $output, $this->channelRegistry->aliases());

        /** @var EntityManagerInterface|null */
        $entityManager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $entityManager) {
            throw new \InvalidArgumentException(\sprintf('Unable to get manager from entity class "%s".', $entityClass));
        }

        if (!\in_array($fromField, $entities[$entityClass] ?? [])) {
            throw new \InvalidArgumentException(\sprintf('The "from-field" "%s" is not mapped for "%s".', $fromField, $entityClass));
        }

        if (!\in_array($toField, $entities[$entityClass] ?? [])) {
            throw new \InvalidArgumentException(\sprintf('The "to-field" "%s" is not mapped for "%s".', $toField, $entityClass));
        }

        $channel = $this->channelRegistry->get($channelAlias);
        $queryBuilder = $entityManager->getRepository($entityClass)
            ->createQueryBuilder('t')
            // ->where(sprintf('t.%s IS NULL', $toField))
            // ->setMaxResults(10)
        ;

        $query = $queryBuilder->getQuery();
        /** @var array<int, object> */
        $result = $query->getResult();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $successfully = 0;
        foreach ($result as $entity) {
            $identifier = is_a($entity, ResourceInterface::class, true)
                ? $propertyAccessor->getValue($entity, 'id')
                : $successfully;

            $message = \sprintf('#%d Execute migrate from %s::%s -> %s',
                $identifier,
                $entityClass,
                $fromField,
                $toField,
            );

            try {
                $toValue = $propertyAccessor->getValue($entity, $toField);
            } catch (\Throwable $th) {
                $toValue = null;
            }

            // Media or subclass of Media
            if (is_a($toValue, Media::class, true)) {
                $output->writeln(\sprintf('<comment>%s already migrated.</comment>', $message));
                continue;
            }

            try {
                $fromValue = $propertyAccessor->getValue($entity, $fromField);
            } catch (\Throwable $th) {
                $fromValue = null;
            }

            if (!\is_string($fromValue)) {
                $output->writeln(\sprintf('<comment>%s invalid value.</comment>', $message));
                continue;
            }

            try {
                $event = $this->createMediaSaveEvent($channel, $fromValue);
            } catch (\Throwable $th) {
                $output->writeln(\sprintf('<comment>%s unable to create event (%s).</comment>', $message, $th->getMessage()));
                continue;
            }

            try {
                $this->eventDispatcher->dispatch($event);
            } catch (\Throwable $th) {
                $output->writeln(\sprintf('<comment>%s unable to migrate.</comment>', $message));
                continue;
            }

            $media = $event->getMedia();
            if (!$media instanceof Media) {
                $output->writeln(\sprintf('<comment>%s unable to migrate.</comment>', $message));
                continue;
            }

            $propertyAccessor->setValue($entity, $toField, $media);

            $entityManager->persist($media);
            $entityManager->flush();

            $output->writeln(\sprintf('<info>%s is successfully.</info>', $message));
            ++$successfully;
        }

        // Clear entity manager
        $entityManager->clear();

        $output->writeln(\sprintf('<info>A total of %d items was migrated.</info>', $successfully));

        return Command::SUCCESS;
    }

    protected function getArgumentForAsk(string $name, InputInterface $input, OutputInterface $output, array $autocompleterValues = [], bool $appendAutocompleterValuesToDesc = true): string
    {
        $value = $input->getArgument($name);
        if (null !== $value) {
            return $value;
        }

        $argument = $this->getDefinition()->getArgument($name);
        $description = $argument->getDescription();
        if ($appendAutocompleterValuesToDesc) {
            $description .= \sprintf(' (%s)', implode(' | ', $autocompleterValues));
        }

        $question = new Question($description);
        $question->setAutocompleterValues($autocompleterValues);
        $question->setMaxAttempts(3);

        return (new SymfonyStyle($input, $output))->askQuestion($question);
    }

    protected function createMediaSaveEvent(ChannelInterface $channel, string $value): MediaSaveEvent
    {
        $path = \sprintf('%s/%s', $this->publicDir, ltrim($value, '/'));
        if (is_file($path)) {
            return MediaSaveEvent::createFromPath($channel, $path);
        } elseif (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return MediaSaveEvent::createFromUrl($channel, $value);
        } else {
            throw new \InvalidArgumentException('invalid file like');
        }
    }

    protected function getEntities(): array
    {
        $entities = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            /** @var ClassMetadataFactory */
            $factory = $em->getMetadataFactory();
            foreach ($factory->getAllMetadata() as $metadata) {
                $name = $metadata->getName();
                if (is_a($name, Media::class, true)) {
                    continue;
                }

                if ($metadata instanceof ClassMetadata) {
                    $entities[$name] = array_keys($metadata->getReflectionProperties());
                }
            }
        }

        // Sort by class name
        ksort($entities);

        return $entities;
    }
}
