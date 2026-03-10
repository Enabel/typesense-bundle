<?php

declare(strict_types=1);

namespace Enabel\Typesense\Bundle\Command;

use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Document\DataProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'enabel:typesense:import', description: 'Import documents into Typesense')]
final class ImportCommand extends Command
{
    /**
     * @param class-string[] $collectionClasses
     * @param array<class-string, DataProviderInterface> $dataProviders
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly array $collectionClasses,
        private readonly array $dataProviders = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('class', null, InputOption::VALUE_REQUIRED, 'Specific class to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $classes = $this->resolveClasses($input);

        foreach ($classes as $className) {
            if (!isset($this->dataProviders[$className])) {
                $io->warning(\sprintf('No data provider registered for %s, skipping.', $className));

                continue;
            }

            $provider = $this->dataProviders[$className];
            $collection = $this->client->collection($className);

            $batch = [];
            $count = 0;

            foreach ($provider->provide($className) as $entity) {
                $batch[] = $entity;
                $count++;

                if (\count($batch) >= 100) {
                    $collection->import($batch);
                    $batch = [];
                }
            }

            if ($batch !== []) {
                $collection->import($batch);
            }

            $io->success(\sprintf('Imported %d documents for %s', $count, $className));
        }

        return Command::SUCCESS;
    }

    /**
     * @return class-string[]
     */
    private function resolveClasses(InputInterface $input): array
    {
        $class = $input->getOption('class');

        if ($class !== null) {
            assert(is_string($class));
            /** @var class-string $class */

            return [$class];
        }

        return $this->collectionClasses;
    }
}
