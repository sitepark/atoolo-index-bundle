<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\IndexerResourceChangeHandler;
use Atoolo\Index\Service\Indexer\InternalResourceIndexer;
use Atoolo\Index\Service\Indexer\UpdatableIndexer;
use Atoolo\Resource\Change\ResourceChange;
use Atoolo\Resource\Change\ResourceChangeDeferredException;
use Atoolo\Resource\Change\ResourceChanges;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexerResourceChangeHandler::class)]
class IndexerResourceChangeHandlerTest extends TestCase
{
    public function testUpdatesAndRemovesInEveryEnabledIndexer(): void
    {
        $solr = $this->createMock(UpdatableIndexer::class);
        $solr->method('enabled')->willReturn(true);
        $solr->expects($this->once())
            ->method('update')
            ->with(['/a.php', '/b.php'])
            ->willReturn(IndexerStatus::empty());
        $solr->expects($this->once())->method('remove')->with(['3']);

        $genai = $this->createMock(InternalResourceIndexer::class);
        $genai->method('enabled')->willReturn(true);
        $genai->method('isIndexing')->willReturn(false);
        $genai->expects($this->once())
            ->method('update')
            ->with(['/a.php', '/b.php'])
            ->willReturn(IndexerStatus::empty());
        $genai->expects($this->once())->method('remove')->with(['3']);

        $disabled = $this->createMock(UpdatableIndexer::class);
        $disabled->method('enabled')->willReturn(false);
        $disabled->expects($this->never())->method('update');
        $disabled->expects($this->never())->method('remove');

        $this->handler([$solr, $genai, $disabled])->handle(new ResourceChanges(
            [
                new ResourceChange('1', '/a.php'),
                new ResourceChange('2', '/b.php'),
            ],
            ['3'],
        ));
    }

    public function testNotUpdatableIndexerOnlyRemoves(): void
    {
        $indexer = $this->createMock(Indexer::class);
        $indexer->method('enabled')->willReturn(true);
        $indexer->expects($this->once())->method('remove')->with(['3']);

        $this->handler([$indexer])->handle(new ResourceChanges(
            [new ResourceChange('1', '/a.php')],
            ['3'],
        ));
    }

    public function testSkipsEmptyLists(): void
    {
        $indexer = $this->createMock(UpdatableIndexer::class);
        $indexer->method('enabled')->willReturn(true);
        $indexer->expects($this->never())->method('update');
        $indexer->expects($this->never())->method('remove');

        $this->handler([$indexer])->handle(new ResourceChanges());
    }

    public function testDefersWhileAnIndexerIsRunning(): void
    {
        $idle = $this->createMock(UpdatableIndexer::class);
        $idle->method('enabled')->willReturn(true);
        $idle->expects($this->never())->method('update');

        $running = $this->createMock(InternalResourceIndexer::class);
        $running->method('enabled')->willReturn(true);
        $running->method('isIndexing')->willReturn(true);
        $running->method('getSource')->willReturn('genai');
        $running->expects($this->never())->method('update');

        $this->expectException(ResourceChangeDeferredException::class);
        $this->expectExceptionMessage('indexer genai is running');

        $this->handler([$idle, $running])->handle(new ResourceChanges(
            [new ResourceChange('1', '/a.php')],
        ));
    }

    /**
     * @param list<Indexer> $indexers
     */
    private function handler(array $indexers): IndexerResourceChangeHandler
    {
        return new IndexerResourceChangeHandler(
            new IndexerCollection($indexers),
        );
    }
}
