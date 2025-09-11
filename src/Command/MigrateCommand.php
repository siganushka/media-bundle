<?php

declare(strict_types=1);

namespace Siganushka\MediaBundle\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Siganushka\Contracts\Doctrine\ResourceInterface;
use Siganushka\MediaBundle\ChannelRegistry;
use Siganushka\MediaBundle\Entity\Media;
use Siganushka\MediaBundle\MediaManagerInterface;
use Siganushka\MediaBundle\Utils\FileUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[AsCommand('siganushka:media:migrate', 'Migrate existing media data to SiganushkaMediaBundle.')]
class MigrateCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly ChannelRegistry $channelRegistry,
        private readonly MediaManagerInterface $mediaManager,
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

        /** @var class-string */
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
            throw new \InvalidArgumentException(\sprintf('The "from-field" with value "%s" is not mapped for "%s".', $fromField, $entityClass));
        }

        if (!\in_array($toField, $entities[$entityClass] ?? [])) {
            throw new \InvalidArgumentException(\sprintf('The "to-field" with value "%s" is not mapped for "%s".', $toField, $entityClass));
        }

        $queryBuilder = $entityManager->getRepository($entityClass)
            ->createQueryBuilder('t')
            // ->where(sprintf('t.%s IS NULL', $toField))
            // ->setMaxResults(10)
        ;

        $query = $queryBuilder->getQuery();
        /** @var array<int, object> */
        $result = $query->getResult();

        $total = \count($result);
        $current = $successfully = 0;

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($result as $entity) {
            ++$current;
            $identifier = $entity instanceof ResourceInterface
                ? $entity->getId()
                : $current;

            $message = \sprintf('[%d/%d] #%d Execute migrate from %s::%s -> %s',
                $current,
                $total,
                $identifier,
                $entityClass,
                $fromField,
                $toField,
            );

            try {
                $toValue = $propertyAccessor->getValue($entity, $toField);
            } catch (\Throwable) {
                $toValue = null;
            }

            if ($toValue instanceof Media || ($toValue instanceof Collection && $toValue->count())) {
                $output->writeln(\sprintf('<comment>%s already migrated.</comment>', $message));
                continue;
            }

            try {
                $fromValue = $propertyAccessor->getValue($entity, $fromField);
            } catch (\Throwable) {
                $fromValue = null;
            }

            if (null === $fromValue) {
                $output->writeln(\sprintf('<comment>%s value is empty.</comment>', $message));
                continue;
            }

            if (!\is_string($fromValue)) {
                $output->writeln(\sprintf('<comment>%s invalid value.</comment>', $message));
                continue;
            }

            try {
                $media = $this->mediaManager->save($channelAlias, $this->createFile($fromValue));
            } catch (\Throwable $th) {
                $output->writeln(\sprintf('<comment>%s unable to migrate (%s).</comment>', $message, $th->getMessage()));
                continue;
            }

            $ref = new \ReflectionProperty($entity, $toField);
            $type = $ref->getType();
            $typeName = $type && $type instanceof \ReflectionNamedType ? $type->getName() : null;

            $propertyAccessor->setValue($entity, $toField, Collection::class === $typeName ? [$media] : $media);

            $entityManager->persist($media);
            $entityManager->flush();

            $output->writeln(\sprintf('<info>%s is successfully.</info>', $message));
            ++$successfully;
        }

        // Clear entity manager
        $entityManager->clear();

        $output->writeln(\sprintf('<info>A total of %d items, %d items was migrated.</info>', $total, $successfully));

        return Command::SUCCESS;
    }

    protected function getArgumentForAsk(string $name, InputInterface $input, OutputInterface $output, array $autocompleterValues = [], bool $appendAutocompleterValuesToDesc = true): string
    {
        /** @var string|null */
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

        $style = new SymfonyStyle($input, $output);
        /** @var string */
        $anwser = $style->askQuestion($question);

        return $anwser;
    }

    protected function createFile(string $value): \SplFileInfo
    {
        if (str_contains($value, '://') || str_starts_with($value, '//')) {
            return FileUtils::createFromUrl($value);
        }

        $originFile = \sprintf('%s/%s', $this->publicDir, ltrim($value, '/'));
        if (!is_file($originFile)) {
            throw new \InvalidArgumentException('invalid file like');
        }

        $targetFile = \sprintf('%s/%s', sys_get_temp_dir(), pathinfo($originFile, \PATHINFO_BASENAME));

        $filesystem = new Filesystem();
        $filesystem->copy($originFile, $targetFile, true);

        return new \SplFileInfo($targetFile);
    }

    protected function getEntities(): array
    {
        $entities = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            /** @var ClassMetadataFactory */
            $factory = $em->getMetadataFactory();
            foreach ($factory->getAllMetadata() as $metadata) {
                $name = $metadata->getName();
                if ($metadata->isMappedSuperclass
                    || $metadata->isEmbeddedClass
                    || is_subclass_of($name, Media::class, true)) {
                    continue;
                }

                $entities[$name] = array_keys($metadata->getPropertyAccessors());
            }
        }

        // Sort by class name
        ksort($entities);

        return $entities;
    }
}
