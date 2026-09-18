<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerMessage;
use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\IndexerMessageHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(IndexerMessageHandler::class)]
class IndexerMessageHandlerTest extends TestCase
{
    public function testInvokeRunsIndexerOfSource(): void
    {
        $indexer = $this->createMock(Indexer::class);
        $indexer->method('getSource')->willReturn('internal');
        $indexer->expects($this->once())
            ->method('index')
            ->willReturn(IndexerStatus::empty());

        $handler = new IndexerMessageHandler(
            new IndexerCollection([$indexer]),
            $this->createStub(LoggerInterface::class),
        );

        $handler(new IndexerMessage('internal'));
    }

    public function testInvokeLogsUnknownSource(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error');

        $handler = new IndexerMessageHandler(
            new IndexerCollection([]),
            $logger,
        );

        $handler(new IndexerMessage('unknown'));
    }
}
