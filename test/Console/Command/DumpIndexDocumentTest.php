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
}
