<?php

declare(strict_types=1);

namespace Enabel\Typesense\Bundle\Command;

use Enabel\Typesense\ClientInterface;
use Enabel\Typesense\Metadata\MetadataRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'enabel:typesense:search', description: 'Search a Typesense collection')]
final class SearchCommand extends Command
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly MetadataRegistryInterface $registry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('class', InputArgument::REQUIRED, 'The document class name')
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Search query (default: *)')
            ->addOption('query-by', null, InputOption::VALUE_REQUIRED, 'Comma-separated fields to search')
            ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter expression (Typesense syntax)')
            ->addOption('per-page', null, InputOption::VALUE_REQUIRED, 'Results per page');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $className = $input->getArgument('class');
        assert(is_string($className));
        /** @var class-string $className */

        $params = [
            'q' => $input->getOption('query') ?? '*',
            'query_by' => $input->getOption('query-by') ?? $this->resolveQueryBy($className),
        ];

        $filter = $input->getOption('filter');
        if ($filter !== null) {
            assert(is_string($filter));
            $params['filter_by'] = $filter;
        }

        $perPage = $input->getOption('per-page');
        if ($perPage !== null) {
            $params['per_page'] = (int) $perPage;
        }

        $result = $this->client->collection($className)->searchRaw($params);

        $io->writeln((string) json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }

    /**
     * @param class-string $className
     */
    private function resolveQueryBy(string $className): string
    {
        $metadata = $this->registry->get($className);

        return implode(',', array_map(
            fn($field) => $field->propertyName,
            array_filter($metadata->fields, fn($field) => $field->index),
        ));
    }
}
