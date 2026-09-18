<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Index\Service\Indexer\DocumentEnricher;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Index\Service\Indexer\IndexDocumentDumper;
use Atoolo\Index\Service\Indexer\IndexDocumentFactory;
use Atoolo\Resource\ResourceLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexDocumentDumper::class)]
class IndexDocumentDumperTest extends TestCase
{
    public function testDump(): void
    {
        $dumper = new IndexDocumentDumper(
            $this->createStub(ResourceLoader::class),
            [$this->createEnricher()],
            $this->createFactory(),
            'test-source',
        );

        $dump = $dumper->dump(['/test.php']);

        $this->assertEquals(
            [['sp_id' => '123']],
            $dump,
            'unexpected dump',
        );
    }

    public function testGetSource(): void
    {
        $dumper = new IndexDocumentDumper(
            $this->createStub(ResourceLoader::class),
            [],
            $this->createFactory(),
            'test-source',
        );

        $this->assertEquals(
            'test-source',
            $dumper->getSource(),
            'unexpected source',
        );
    }

    private function createFactory(): IndexDocumentFactory
    {
        $document = $this->createStub(IndexDocument::class);
        $document->method('getFields')->willReturn([]);

        $factory = $this->createStub(IndexDocumentFactory::class);
        $factory->method('create')->willReturn($document);

        return $factory;
    }

    /**
     * @return DocumentEnricher<IndexDocument>
     */
    private function createEnricher(): DocumentEnricher
    {
        $enriched = $this->createStub(IndexDocument::class);
        $enriched->method('getFields')->willReturn(['sp_id' => '123']);

        $enricher = $this->createStub(DocumentEnricher::class);
        $enricher->method('enrichDocument')->willReturn($enriched);

        return $enricher;
    }
}
