<?php

declare(strict_types=1);

namespace Enabel\Typesense\Bundle\Command;

use Enabel\Typesense\ClientInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'enabel:typesense:create', description: 'Create Typesense collections')]
final class CreateCommand extends Command
{
    /**
     * @param class-string[] $collectionClasses
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly array $collectionClasses,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('class', null, InputOption::VALUE_REQUIRED, 'Specific class to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $classes = $this->resolveClasses($input);

        foreach ($classes as $className) {
            $this->client->create($className);
            $io->success(\sprintf('Created collection for %s', $className));
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
