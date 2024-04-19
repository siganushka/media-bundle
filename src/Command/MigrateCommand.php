<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectRepository;
use Siganushka\Contracts\Registry\Exception\ServiceNonExistingException;
use Siganushka\MediaBundle\ChannelInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\Event\MediaSaveEvent;
use Siganushka\MediaBundle\Exception\UnsupportedChannelException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'siganushka:media:migrate';
    protected static $defaultDescription = 'Migrate existing data to SiganushkaMediaBundle.';

    private EventDispatcherInterface $eventDispatcher;
    private ManagerRegistry $managerRegistry;
    private ChannelRegistry $channelRegistry;
    private string $publicDir;

    public function __construct(EventDispatcherInterface $eventDispatcher, ManagerRegistry $managerRegistry, ChannelRegistry $channelRegistry, string $publicDir)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->managerRegistry = $managerRegistry;
        $this->channelRegistry = $channelRegistry;
        $this->publicDir = $publicDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'Which entity do you want to migrate?')
            ->addArgument('from-field', InputArgument::OPTIONAL, 'Which field do you want to migrate from?')
            ->addArgument('to-field', InputArgument::OPTIONAL, 'Which field do you want to migrate to?')
            ->addArgument('channel-alias', InputArgument::OPTIONAL, 'Which channel to use?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entities = $this->getEntities();

        /** @psalm-var class-string */
        $entityClass = $this->getArgumentForAsk('entity-class', $input, $output, array_keys($entities));
        $fromField = $this->getArgumentForAsk('from-field', $input, $output, $entities[$entityClass] ?? []);
        $toField = $this->getArgumentForAsk('to-field', $input, $output, $entities[$entityClass] ?? []);
        $channelAlias = $this->getArgumentForAsk('channel-alias', $input, $output, $this->channelRegistry->getServiceIds());

        $objectManager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $objectManager) {
            throw new \InvalidArgumentException(sprintf('Unable to get manager from entity class "%s".', $entityClass));
        }

        if (!\in_array($fromField, $entities[$entityClass] ?? [])) {
            throw new \InvalidArgumentException(sprintf('The "from-field" "%s" is not mapped for "%s".', $fromField, $entityClass));
        }

        if (!\in_array($toField, $entities[$entityClass] ?? [])) {
            throw new \InvalidArgumentException(sprintf('The "to-field" "%s" is not mapped for "%s".', $toField, $entityClass));
        }

        try {
            $channel = $this->channelRegistry->get($channelAlias);
        } catch (ServiceNonExistingException $th) {
            throw new UnsupportedChannelException($this->channelRegistry, $channelAlias);
        }

        /** @var ObjectRepository */
        $repository = $objectManager->getRepository($entityClass);
        $result = $repository->findAll();

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $successfully = 0;
        foreach ($result as $entity) {
            try {
                $toValue = $propertyAccessor->getValue($entity, $toField);
            } catch (\Throwable $th) {
                $toValue = null;
            }

            if ($toValue instanceof Media) {
                $output->writeln(sprintf('<comment>[Skip] Execute migrate from %s::%s => %s is already migrated.</comment>', $entityClass, $fromField, $toField));
                continue;
            }

            try {
                $fromValue = $propertyAccessor->getValue($entity, $fromField);
            } catch (\Throwable $th) {
                $fromValue = null;
            }

            if (!\is_string($fromValue)) {
                $output->writeln(sprintf('<comment>[Skip] Execute migrate from %s::%s(%s) Invalid file like.</comment>', $entityClass, $fromField, $fromValue));
                continue;
            }

            try {
                $event = $this->createMediaSaveEvent($channel, $fromValue);
            } catch (\Throwable $th) {
                $output->writeln(sprintf('<comment>[Skip] Execute migrate from %s::%s(%s) Unable to create event (%s).</comment>', $entityClass, $fromField, $fromValue, $th->getMessage()));
                continue;
            }

            if (null === $event) {
                $output->writeln(sprintf('<comment>[Skip] Execute migrate from %s::%s(%s) Invalid file like.</comment>', $entityClass, $fromField, $fromValue));
                continue;
            }

            $this->eventDispatcher->dispatch($event);

            $media = $event->getMedia();
            if (!$media instanceof Media) {
                $output->writeln(sprintf('<comment>[Skip] Execute migrate from %s::%s(%s) Unable to migrate.</comment>', $entityClass, $fromField, $fromValue));
                continue;
            }

            try {
                $propertyAccessor->setValue($entity, $toField, $media);
            } catch (\Throwable $th) {
                $output->writeln(sprintf('<comment>[Skip] Execute migrate from %s::%s(%s) => %s cannot be set with new data.</comment>', $entityClass, $fromField, $fromValue, $toField));
                continue;
            }

            $objectManager->persist($media);

            $output->writeln(sprintf('<info>Execute migrate from %s::%s(%s) => %s(%s) successfully.</info>', $entityClass, $fromField, $fromValue, $toField, $media->getUrl()));
            ++$successfully;
        }

        $objectManager->flush();
        $output->writeln(sprintf('<info>A total of %d record was migrated.</info>', $successfully));

        return Command::SUCCESS;
    }

    protected function getArgumentForAsk(string $name, InputInterface $input, OutputInterface $output, ?iterable $autocompleterValues = []): string
    {
        $value = $input->getArgument($name);
        if (null !== $value) {
            return $value;
        }

        $argument = $this->getDefinition()->getArgument($name);

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues($autocompleterValues);
        $question->setMaxAttempts(3);

        return (new SymfonyStyle($input, $output))->askQuestion($question);
    }

    protected function createMediaSaveEvent(ChannelInterface $channel, string $value): ?MediaSaveEvent
    {
        $path = sprintf('%s/%s', rtrim($this->publicDir), ltrim($value));
        if (is_file($path)) {
            return MediaSaveEvent::createFromPath($channel, $path);
        } elseif (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return MediaSaveEvent::createFromUrl($channel, $value);
        } else {
            return null;
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
                if (Media::class !== $name) {
                    $entities[$name] = array_keys($metadata->reflFields);
                }
            }
        }

        ksort($entities);

        return $entities;
    }
}
