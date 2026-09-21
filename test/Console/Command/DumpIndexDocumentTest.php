<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Console\Command;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Index\Console\Application;
use Atoolo\Index\Console\Command\DumpIndexDocument;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Index\Service\Indexer\IndexDocumentDumper;
use Atoolo\Index\Service\Indexer\IndexDocumentDumperCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(DumpIndexDocument::class)]
class DumpIndexDocumentTest extends TestCase
{
    private CommandTester $commandTester;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $resourceTanent = $this->createMock(ResourceTenant::class);
        $resourceChannel = new ResourceChannel(
            '',
            'WWW',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            '',
            'test',
            [],
            new DataBag([]),
            $resourceTanent,
        );

        $dumper = $this->createStub(IndexDocumentDumper::class);
        $dumper->method('getSource')
            ->willReturn('internal');
        $document = $this->createStub(IndexDocument::class);
        $document->method('jsonSerialize')->willReturn(['id' => '123']);
        $dumper->method('dump')
            ->willReturn([$document]);

        $dumperCommand = new DumpIndexDocument(
            $resourceChannel,
            new IndexDocumentDumperCollection([$dumper]),
        );

        $application = new Application([
            $dumperCommand,
        ]);

        $command = $application->find('index:dump-document');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->commandTester->execute([
            'paths' => ['test.php'],
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW (source: internal)
===============================

{
    "id": "123"
}

EOF,
            $output,
        );
    }

    public function testExecuteWithoutDumper(): void
    {
        $tester = $this->createTester([]);

        $tester->execute(['paths' => ['test.php']]);

        $this->assertEquals(
            Command::FAILURE,
            $tester->getStatusCode(),
            'without a dumper the command should fail',
        );
        $this->assertStringContainsString(
            'No index document dumper available',
            $tester->getDisplay(),
        );
    }

    public function testExecuteSelectsDumperBySource(): void
    {
        $tester = $this->createTester([
            $this->createDumper('internal'),
            $this->createDumper('genai'),
        ]);

        $tester->execute(['paths' => ['test.php'], '--source' => 'genai']);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            '(source: genai)',
            $tester->getDisplay(),
            'the --source option should pick the dumper',
        );
    }

    public function testExecuteAsksForSource(): void
    {
        $tester = $this->createTester([
            $this->createDumper('internal'),
            $this->createDumper('genai'),
        ]);

        $tester->setInputs(['1']);
        $tester->execute(['paths' => ['test.php']]);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            'You have just selected: genai',
            $tester->getDisplay(),
            'with several dumpers the command should ask',
        );
    }

    /**
     * @param IndexDocumentDumper[] $dumpers
     */
    private function createTester(array $dumpers): CommandTester
    {
        $resourceChannel = new ResourceChannel(
            '',
            'WWW',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            '',
            'test',
            [],
            new DataBag([]),
            $this->createMock(ResourceTenant::class),
        );
        $command = new DumpIndexDocument(
            $resourceChannel,
            new IndexDocumentDumperCollection($dumpers),
        );
        $application = new Application([$command]);
        return new CommandTester($application->find('index:dump-document'));
    }

    private function createDumper(string $source): IndexDocumentDumper
    {
        $document = $this->createStub(IndexDocument::class);
        $document->method('jsonSerialize')->willReturn(['id' => $source]);
        $dumper = $this->createStub(IndexDocumentDumper::class);
        $dumper->method('getSource')->willReturn($source);
        $dumper->method('dump')->willReturn([$document]);
        return $dumper;
    }
}
