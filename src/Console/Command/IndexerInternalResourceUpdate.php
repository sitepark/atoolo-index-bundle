<?php

declare(strict_types=1);

namespace Atoolo\Index\Console\Command;

use Atoolo\Index\Console\Command\Io\IndexerProgressBar;
use Atoolo\Index\Console\Command\Io\TypifiedInput;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\UpdatableIndexer;
use Atoolo\Resource\ResourceChannel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'index:update',
    description: 'Update resources in the index',
)]
class IndexerInternalResourceUpdate extends Command
{
    private SymfonyStyle $io;
    private OutputInterface $output;

    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly IndexerProgressBar $progressBar,
        private readonly IndexerCollection $indexers,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to update resources in the index')
            ->addArgument(
                'paths',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be updated.',
            )
            ->addOption(
                'source',
                null,
                InputArgument::OPTIONAL,
                'Uses only the indexer of a specific source',
                '',
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {

        $typedInput = new TypifiedInput($input);
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $source = $typedInput->getStringOption('source');
        $paths = $typedInput->getArrayArgument('paths');

        $this->io->title('Channel: ' . $this->channel->name);

        $selectable = $this->getSelectableIndexer($source);
        if (empty($selectable)) {
            $this->io->error('No updatable indexer available');
            return Command::FAILURE;
        }

        foreach ($selectable as $indexer) {
            $this->update($indexer, $paths);
        }

        return Command::SUCCESS;
    }

    /**
     * @return UpdatableIndexer[]
     */
    private function getSelectableIndexer(string $source): array
    {
        $selectable = [];
        foreach ($this->indexers->getIndexers() as $indexer) {
            if (!$indexer instanceof UpdatableIndexer) {
                continue;
            }
            if (!empty($source) && $indexer->getSource() !== $source) {
                continue;
            }
            if ($indexer->enabled()) {
                $selectable[] = $indexer;
            }
        }
        return $selectable;
    }

    /**
     * @param array<string> $paths
     */
    private function update(UpdatableIndexer $indexer, array $paths): void
    {
        $this->io->newLine();
        $this->io->section(
            'Index resource paths with Indexer "'
            . $indexer->getName() . '" '
            . '(source: ' . $indexer->getSource() . ')',
        );
        $this->io->listing($paths);
        $progressHandler = $indexer->getProgressHandler();
        $this->progressBar->init($progressHandler);
        $indexer->setProgressHandler($this->progressBar);
        try {
            $status = $indexer->update($paths);
        } finally {
            $indexer->setProgressHandler($progressHandler);
        }
        $this->io->newLine(2);
        $this->io->section("Status");
        $this->io->text($status->getStatusLine());
        $this->io->newLine();
        $this->errorReport();
    }

    protected function errorReport(): void
    {
        if (empty($this->progressBar->getErrors())) {
            return;
        }
        $this->io->section("Error Report");

        foreach ($this->progressBar->getErrors() as $error) {
            if ($this->io->isVerbose() && $this->getApplication() !== null) {
                $this->getApplication()->renderThrowable($error, $this->output);
            } else {
                $this->io->error($error->getMessage());
            }
        }
    }
}
