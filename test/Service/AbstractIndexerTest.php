<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service;

use Atoolo\Resource\DataBag;
use Atoolo\Index\Dto\Indexer\IndexerConfiguration;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\AbstractIndexer;
use Atoolo\Index\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Index\Service\Indexer\IndexerProgressHandler;
use Atoolo\Index\Service\Indexer\IndexingAborter;
use Atoolo\Index\Service\IndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractIndexer::class)]
class AbstractIndexerTest extends TestCase
{
    private Indexer $indexer;

    private IndexerProgressHandler $progressHandler;

    private IndexingAborter&MockObject $aborter;

    private IndexerConfigurationLoader&MockObject $configLoader;

    private IndexName $indexName;

    public function setUp(): void
    {
        $this->indexName = $indexName = $this->createMock(IndexName::class);
        $indexName->method('name')
            ->willReturn('www');
        $this->progressHandler = $this->createMock(
            IndexerProgressHandler::class,
        );
        $this->aborter = $this->createMock(IndexingAborter::class);
        $config = new IndexerConfiguration(
            'test',
            'Test',
            new DataBag([]),
        );
        $this->configLoader = $this->createMock(
            IndexerConfigurationLoader::class,
        );
        $this->configLoader->method('load')
            ->willReturn($config);
        $this->indexer = new TextIndexer(
            $indexName,
            $this->progressHandler,
            $this->aborter,
            $this->configLoader,
            'test',
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            'Test',
            $this->indexer->getName(),
            'The name of the indexer should be "Test"',
        );
    }

    public function testGetSource(): void
    {
        $this->assertEquals(
            'test',
            $this->indexer->getSource(),
            'The source of the indexer should be "test"',
        );
    }

    public function testIdDefaultsToSource(): void
    {
        $this->assertEquals(
            'test',
            $this->indexer->getId(),
            'without an id of its own the indexer should be known by its '
            . 'source',
        );
    }

    public function testIdDiffersFromSource(): void
    {
        $indexer = $this->createIndexerWithId();

        $this->assertEquals(
            ['genai', 'internal'],
            [$indexer->getId(), $indexer->getSource()],
            'id and source should be kept apart',
        );
    }

    public function testConfigIsLoadedById(): void
    {
        $this->configLoader->expects($this->once())
            ->method('load')
            ->with('genai');
        $this->createIndexerWithId()->getName();
    }

    public function testEnabledById(): void
    {
        $this->configLoader->expects($this->once())
            ->method('exists')
            ->with('genai');
        $this->createIndexerWithId()->enabled();
    }

    public function testAbortById(): void
    {
        $this->aborter->expects($this->once())
            ->method('requestAbortion')
            ->with('www-genai');
        $this->createIndexerWithId()->abort();
    }

    public function testGetProgressHandler(): void
    {
        $this->assertEquals(
            $this->progressHandler,
            $this->indexer->getProgressHandler(),
            'The progress handler should be the '
            . 'same as the one passed to the constructor',
        );
    }

    public function testSetProgressHandler(): void
    {
        $progressHandler = $this->createStub(IndexerProgressHandler::class);
        $this->indexer->setProgressHandler($progressHandler);

        $this->assertEquals(
            $progressHandler,
            $this->indexer->getProgressHandler(),
            'The progress handler should be the '
            . 'same as the one passed to the setProgressHandler method',
        );
    }

    public function testAbort(): void
    {

        $this->aborter->expects($this->once())
            ->method('requestAbortion')
            ->with('www-test');
        $this->indexer->abort();
    }

    public function testEnabled(): void
    {

        $this->configLoader->expects($this->once())
            ->method('exists')
            ->with('test');
        $this->indexer->enabled();
    }

    public function testIsAbortionRequested(): void
    {

        $this->aborter->expects($this->once())
            ->method('isAbortionRequested')
            ->with('www-test');
        $this->indexer->isAbortionRequested();
    }

    private function createIndexerWithId(): TextIndexer
    {
        return new TextIndexer(
            $this->indexName,
            $this->progressHandler,
            $this->aborter,
            $this->configLoader,
            'internal',
            'genai',
        );
    }
}
