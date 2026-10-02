<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerId;
use Atoolo\Index\Service\Indexer\InternalResourceIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexerId::class)]
class IndexerIdTest extends TestCase
{
    public function testOfAbstractIndexer(): void
    {
        $indexer = $this->createStub(InternalResourceIndexer::class);
        $indexer->method('getId')->willReturn('genai');
        $indexer->method('getSource')->willReturn('internal');

        $this->assertEquals(
            'genai',
            IndexerId::of($indexer),
            'the id of the indexer should be used',
        );
    }

    public function testOfPlainIndexer(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexer->method('getSource')->willReturn('internal');

        $this->assertEquals(
            'internal',
            IndexerId::of($indexer),
            'an indexer without an id should be known by its source',
        );
    }

    public function testLabelWithSameIdAndSource(): void
    {
        $this->assertEquals(
            '(source: internal)',
            IndexerId::label('internal', 'internal'),
            'the id should only be named where it differs',
        );
    }

    public function testLabelWithDifferentIdAndSource(): void
    {
        $this->assertEquals(
            '(id: genai, source: internal)',
            IndexerId::label('genai', 'internal'),
            'both should be named where they differ',
        );
    }
}
