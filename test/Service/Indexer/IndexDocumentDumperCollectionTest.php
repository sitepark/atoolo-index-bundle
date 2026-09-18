<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Index\Service\Indexer\IndexDocumentDumper;
use Atoolo\Index\Service\Indexer\IndexDocumentDumperCollection;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexDocumentDumperCollection::class)]
class IndexDocumentDumperCollectionTest extends TestCase
{
    public function testGetDumper(): void
    {
        $dumper = $this->createStub(IndexDocumentDumper::class);
        $dumper->method('getSource')->willReturn('test');

        $collection = new IndexDocumentDumperCollection([$dumper]);

        $this->assertEquals(
            $dumper,
            $collection->getDumper('test'),
            'unexpected dumper',
        );
    }

    public function testGetUnknownDumper(): void
    {
        $collection = new IndexDocumentDumperCollection([]);

        $this->expectException(InvalidArgumentException::class);
        $collection->getDumper('test');
    }

    public function testGetDumpers(): void
    {
        $dumper = $this->createStub(IndexDocumentDumper::class);
        $collection = new IndexDocumentDumperCollection([$dumper]);

        $this->assertEquals(
            [$dumper],
            $collection->getDumpers(),
            'unexpected dumpers',
        );
    }

    public function testGetDumpersFromTraversable(): void
    {
        $dumper = $this->createStub(IndexDocumentDumper::class);
        $collection = new IndexDocumentDumperCollection(
            new \ArrayIterator([$dumper]),
        );

        $this->assertEquals(
            [$dumper],
            $collection->getDumpers(),
            'unexpected dumpers',
        );
    }
}
