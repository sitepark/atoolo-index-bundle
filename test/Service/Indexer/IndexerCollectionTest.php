<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use ArrayIterator;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\InternalResourceIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexerCollection::class)]
class IndexerCollectionTest extends TestCase
{
    public function testGetIndexer(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexer->method('getSource')->willReturn('test');
        $indexers = new IndexerCollection([$indexer]);
        $this->assertNotNull($indexers->getIndexer('test'));
    }

    public function testGetIndexerById(): void
    {
        $solr = $this->createStub(InternalResourceIndexer::class);
        $solr->method('getId')->willReturn('internal');
        $solr->method('getSource')->willReturn('internal');
        $genai = $this->createStub(InternalResourceIndexer::class);
        $genai->method('getId')->willReturn('genai');
        $genai->method('getSource')->willReturn('internal');

        $indexers = new IndexerCollection([$solr, $genai]);

        $this->assertSame(
            $genai,
            $indexers->getIndexer('genai'),
            'indexers sharing a source should be told apart by their id',
        );
    }

    public function testGetMissingIndexer(): void
    {
        $indexers = new IndexerCollection([]);
        $this->expectException(\InvalidArgumentException::class);
        $indexers->getIndexer('test');
    }

    public function testGetIndexers(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexers = new IndexerCollection([$indexer]);
        $this->assertCount(
            1,
            $indexers->getIndexers(),
            'unexpected number of indexers',
        );
    }

    public function testGetIndexersWithIterable(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexers = new IndexerCollection(new ArrayIterator([$indexer]));
        $this->assertCount(
            1,
            $indexers->getIndexers(),
            'unexpected number of indexers',
        );
    }
}
