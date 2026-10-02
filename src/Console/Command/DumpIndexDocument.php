<?php

declare(strict_types=1);

namespace Atoolo\Index\Console\Command;

use Atoolo\Index\Console\Command\Io\TypifiedInput;
use Atoolo\Index\Service\Indexer\IndexDocumentDumper;
use Atoolo\Index\Service\Indexer\IndexDocumentDumperCollection;
use Atoolo\Index\Service\Indexer\IndexerId;
use Atoolo\Resource\ResourceChannel;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'index:dump-document',
    description: 'Dump a index document',
)]
class DumpIndexDocument extends Command
{
    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly IndexDocumentDumperCollection $dumpers,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to dump a index-document')
            ->addArgument(
                'paths',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be indexed.',
            )
            ->addOption(
                'source',
                null,
                InputArgument::OPTIONAL,
                'Uses only the document dumpers of a specific source',
                '',
            )
            ->addOption(
                'indexer',
                null,
                InputArgument::OPTIONAL,
                'Uses only the document dumper of the indexer with this id',
                '',
            )
        ;
    }

    /**
     * The source whose document is to be dumped. Several indexers may share
     * a source, so pinning it does not single one out; pin the id instead.
     */
    protected function getRequestedSource(TypifiedInput $input): string
    {
        return $input->getStringOption('source');
    }

    /**
     * The id of the indexer whose document is to be dumped. Subclasses can
     * pin the id, so that their output never changes.
     */
    protected function getRequestedId(TypifiedInput $input): string
    {
        return $input->getStringOption('indexer');
    }

    /**
     * @throws JsonException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {

        $typedInput = new TypifiedInput($input);

        $paths = $typedInput->getArrayArgument('paths');
        $source = $this->getRequestedSource($typedInput);
        $id = $this->getRequestedId($typedInput);

        $io = new SymfonyStyle($input, $output);

        $selectable = $this->getSelectableDumper($source, $id);
        if (empty($selectable)) {
            $io->title('Channel: ' . $this->channel->name);
            $io->error('No index document dumper available');
            return Command::FAILURE;
        }

        $dumper = $this->selectDumper($selectable, $input, $output, $io);

        $io->title(
            'Channel: ' . $this->channel->name
            . ' ' . IndexerId::label($dumper->getId(), $dumper->getSource()),
        );

        $dump = $dumper->dump($paths);

        foreach ($dump as $document) {
            $output->writeln(json_encode(
                $document,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * @return IndexDocumentDumper[]
     */
    private function getSelectableDumper(string $source, string $id): array
    {
        $selectable = [];
        foreach ($this->dumpers->getDumpers() as $dumper) {
            if (!empty($source) && $dumper->getSource() !== $source) {
                continue;
            }
            if (!empty($id) && $dumper->getId() !== $id) {
                continue;
            }
            $selectable[] = $dumper;
        }
        return $selectable;
    }

    /**
     * @param IndexDocumentDumper[] $selectable
     */
    private function selectDumper(
        array $selectable,
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
    ): IndexDocumentDumper {
        if (count($selectable) === 1) {
            return $selectable[0];
        }

        $ids = [];
        foreach ($selectable as $dumper) {
            $ids[] = $dumper->getId();
        }
        $io->newLine();
        $io->section('Several index document dumpers are available.');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select the indexer you want to use [0]',
            $ids,
        );
        $question->setErrorMessage('Indexer %s is invalid.');

        /** @var string $selectedId */
        $selectedId = $helper->ask($input, $output, $question);
        $io->text('You have just selected: ' . $selectedId);

        $pos = array_search($selectedId, $ids, true);
        return $selectable[$pos];
    }
}
