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
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
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
            ->addArgument('channel-alias', InputArgument::OPTIONAL, 'Which channel to migrate to?')
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'The entity class name that to migrate.')
            ->addArgument('entity-field', InputArgument::OPTIONAL, 'The entity field name that to migrate.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entitiesMapping = $this->getEntities();

        $channelAlias = $this->getArgumentForAsk('channel-alias', $input, $output, $this->channelRegistry->getServiceIds());
        $entityClass = $this->getArgumentForAsk('entity-class', $input, $output, array_keys($entitiesMapping));
        $entityField = $this->getArgumentForAsk('entity-field', $input, $output, $entitiesMapping[$entityClass] ?? []);

        try {
            $channel = $this->channelRegistry->get($channelAlias);
        } catch (ServiceNonExistingException $th) {
            throw new UnsupportedChannelException($this->channelRegistry, $channelAlias);
        }

        $objectManager = $this->managerRegistry->getManagerForClass($entityClass);
        if (null === $objectManager) {
            throw new \InvalidArgumentException(sprintf('Invalid entity class "%s" for object manager.', $entityClass));
        }

        /** @var ObjectRepository */
        $repository = $objectManager->getRepository($entityClass);
        $result = $repository->findAll();

        $successfully = 0;
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($result as $entity) {
            try {
                $value = $propertyAccessor->getValue($entity, $entityField);
            } catch (\Throwable $th) {
                throw new \InvalidArgumentException(sprintf('The field "%s" is not mapped for "%s".', $entityField, $entityClass));
            }

            if (!\is_string($value)) {
                $output->writeln(sprintf('<comment>[Skip] %s::%s (%s) Invalid file like.</comment>', $entityClass, $entityField, $value));
                continue;
            }

            try {
                $event = $this->createMediaSaveEvent($channel, $value);
            } catch (\Throwable $th) {
                $output->writeln(sprintf('<comment>[Skip] %s::%s (%s) Unable to create event (%s).</comment>', $entityClass, $entityField, $value, $th->getMessage()));
                continue;
            }

            if (null === $event) {
                $output->writeln(sprintf('<comment>[Skip] %s::%s (%s) Invalid file like.</comment>', $entityClass, $entityField, $value));
                continue;
            }

            $this->eventDispatcher->dispatch($event);

            $media = $event->getMedia();
            if (!$media instanceof Media) {
                $output->writeln(sprintf('<comment>[Skip] %s::%s (%s) Unable to migrate.</comment>', $entityClass, $entityField, $value));
                continue;
            }

            $objectManager->persist($media);

            $output->writeln(sprintf('<info>%s::%s (%s) migrate successfully.</info>', $entityClass, $entityField, $value));
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

        return (new ConsoleStyle($input, $output))->askQuestion($question);
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
                    $entities[$name] = $metadata->getFieldNames();
                }
            }
        }

        ksort($entities);

        return $entities;
    }
}
