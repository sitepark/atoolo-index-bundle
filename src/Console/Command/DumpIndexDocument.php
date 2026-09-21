<?php

declare(strict_types=1);

namespace Atoolo\Index\Console\Command;

use Atoolo\Index\Console\Command\Io\TypifiedInput;
use Atoolo\Index\Service\Indexer\IndexDocumentDumper;
use Atoolo\Index\Service\Indexer\IndexDocumentDumperCollection;
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
                'Uses only the document dumper of a specific source',
                '',
            )
        ;
    }

    /**
     * The source whose document is to be dumped. Subclasses can pin the
     * source, so that their output never changes.
     */
    protected function getRequestedSource(TypifiedInput $input): string
    {
        return $input->getStringOption('source');
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

        $io = new SymfonyStyle($input, $output);

        $selectable = $this->getSelectableDumper($source);
        if (empty($selectable)) {
            $io->title('Channel: ' . $this->channel->name);
            $io->error('No index document dumper available');
            return Command::FAILURE;
        }

        $dumper = $this->selectDumper($selectable, $input, $output, $io);

        $io->title(
            'Channel: ' . $this->channel->name
            . ' (source: ' . $dumper->getSource() . ')',
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
    private function getSelectableDumper(string $source): array
    {
        $selectable = [];
        foreach ($this->dumpers->getDumpers() as $dumper) {
            if (!empty($source) && $dumper->getSource() !== $source) {
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

        $sources = [];
        foreach ($selectable as $dumper) {
            $sources[] = $dumper->getSource();
        }
        $io->newLine();
        $io->section('Several index document dumpers are available.');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select the source you want to use [0]',
            $sources,
        );
        $question->setErrorMessage('Source %s is invalid.');

        /** @var string $selectedSource */
        $selectedSource = $helper->ask($input, $output, $question);
        $io->text('You have just selected: ' . $selectedSource);

        $pos = array_search($selectedSource, $sources, true);
        return $selectable[$pos];
    }
}
