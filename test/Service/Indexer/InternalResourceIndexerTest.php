<?php

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Index\Dto\Indexer\IndexerConfiguration;
use Atoolo\Index\Exception\UnsupportedIndexLanguageException;
use Atoolo\Index\Service\Indexer\DocumentEnricher;
use Atoolo\Index\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Index\Service\Indexer\IndexerProgressHandler;
use Atoolo\Index\Service\Indexer\IndexingAborter;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Index\Service\Indexer\IndexService;
use Atoolo\Index\Service\Indexer\IndexUpdateResult;
use Atoolo\Index\Service\Indexer\IndexUpdater;
use Atoolo\Index\Service\Indexer\InternalResourceIndexer;
use Atoolo\Index\Service\Indexer\LocationFinder;
use Atoolo\Index\Service\Indexer\ResourceFilter;
use Atoolo\Index\Service\IndexName;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

#[CoversClass(InternalResourceIndexer::class)]
class InternalResourceIndexerTest extends TestCase
{
    /**
    * @var string[]
     */
    private array $availableIndexes = ['test', 'test-en_US'];

    public ResourceFilter&MockObject $indexerFilter;

    private ResourceLoader&Stub $resourceLoader;

    private IndexerProgressHandler&MockObject $indexerProgressHandler;

    private InternalResourceIndexer $indexer;

    private IndexService&MockObject $indexService;

    private LocationFinder&MockObject $finder;

    private IndexUpdater&MockObject $updater;

    private IndexUpdateResult&Stub $updateResult;

    private IndexingAborter&MockObject $aborter;

    private DocumentEnricher&MockObject $documentEnricher;

    private IndexerConfigurationLoader&MockObject $indexerConfigurationLoader;

    private IndexerConfiguration $indexerConfiguration;

    private LockFactory $lockFactory;

    private LoggerInterface&MockObject $logger;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->indexerFilter = $this->createMock(
            ResourceFilter::class,
        );

        $this->indexerProgressHandler = $this->createMock(
            IndexerProgressHandler::class,
        );
        $this->finder = $this->createMock(LocationFinder::class);
        $this->documentEnricher = $this->createMock(DocumentEnricher::class);
        $this->documentEnricher
            ->method('enrichDocument')
            ->willReturnCallback(function ($resource, $doc) {
                return $doc;
            });
        $this->resourceLoader = $this->createStub(ResourceLoader::class);
        $this->resourceLoader->method('load')
            ->willReturnCallback(function ($location) {
                $resourceLang = $location->lang;
                if ($location->location === '/a/en.php') {
                    $resourceLang = ResourceLanguage::of('en_EN');
                }
                return new Resource(
                    $location->location,
                    '',
                    '',
                    '',
                    $resourceLang,
                    new DataBag([]),
                );
            });
        $this->indexService = $this->createMock(IndexService::class);
        $this->updateResult = $this->createStub(IndexUpdateResult::class);
        $this->updater = $this->createMock(IndexUpdater::class);
        $this->updater->method('update')->willReturn($this->updateResult);
        $this->updater->method('createDocument')->willReturnCallback(
            fn() => $this->createStub(IndexDocument::class),
        );
        $this->indexService->method('getManagedIndices')
            ->willReturnCallback(function () {
                return $this->availableIndexes;
            });
        $this->indexService->method('getIndex')
            ->willReturnCallback(function ($lang) {
                if ($lang->code === 'en') {
                    return 'test-en_US';
                }
                if ($lang->code === 'fr') {
                    throw new UnsupportedIndexLanguageException(
                        'test',
                        $lang,
                        'unsupported language',
                    );
                }
                return 'test';
            });
        $this->indexService->method('updater')
            ->willReturn($this->updater);
        $this->aborter =  $this->createMock(IndexingAborter::class);
        $this->indexerConfiguration = new IndexerConfiguration(
            'test-source',
            'Indexer-Name',
            new DataBag([
                'cleanupThreshold' =>  10,
                'chunkSize' => 10,
            ]),
        );
        $this->indexerConfigurationLoader = $this->createMock(
            IndexerConfigurationLoader::class,
        );
        $this->indexerConfigurationLoader->method('load')
            ->willReturn($this->indexerConfiguration);

        $this->lockFactory = new LockFactory(new SemaphoreStore());
        $this->logger = $this->createMock(LoggerInterface::class);

        $indexName = $this->createStub(IndexName::class);
        $indexName->method('name')->willReturn('test');

        $this->indexer = new InternalResourceIndexer(
            [ $this->documentEnricher ],
            $this->indexerFilter,
            $this->indexerProgressHandler,
            $this->finder,
            $this->resourceLoader,
            $this->indexService,
            $this->aborter,
            $this->indexerConfigurationLoader,
            'test-source',
            $indexName,
            true,
            null,
            $this->logger,
            $this->lockFactory,
        );
    }

    public function testGetIndex(): void
    {
        $this->assertEquals(
            'test',
            $this->indexer->getIndex(ResourceLanguage::default()),
            'unexpected index',
        );
    }

    public function testGetStatus(): void
    {
        $this->indexerProgressHandler->expects($this->once())
            ->method('getStatus');
        $this->indexer->getStatus();
    }

    public function testAbort(): void
    {
        $this->aborter->expects($this->once())
            ->method('requestAbortion')
            ->with('test-test-source');

        $this->indexer->abort();
    }

    public function testRemove(): void
    {
        $this->indexService->expects($this->once())
            ->method('deleteByIdListForAllLanguages');

        $this->indexer->remove(['123']);
    }

    public function testRemoveEmpty(): void
    {
        $this->indexService->expects($this->exactly(0))
            ->method('deleteByIdListForAllLanguages');

        $this->indexer->remove([]);
    }

    public function testIndexAllWithChunks(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/b.php.translations/en_US.php',
                '/a/c.php',
                '/a/c.php.translations/fr_FR.php',
                '/a/d.php',
                '/a/e.php',
                '/a/f.php',
                '/a/g.php',
                '/a/h.php',
                '/a/i.php',
                '/a/j.php',
                '/a/k.php',
                '/a/l.php',
                '/a/error.php',
            ]);

        $this->updateResult->method('isSuccess')
            ->willReturn(true);
        $this->indexerFilter->method('accept')
            ->willReturn(true);

        $this->documentEnricher
            ->method('enrichDocument')
            ->willReturnCallback(function ($resource, $doc) {
                if ($resource->location === '/a/error.php') {
                    throw new RuntimeException('test');
                }
                return $doc;
            });

        $this->updater->expects($this->exactly(12))
            ->method('addDocument');

        $this->updater->expects($this->exactly(3))
            ->method('update');

        $this->indexerProgressHandler->expects($this->exactly(2))
            ->method('error');

        $this->indexer->index();
    }

    public function testIndexAllWithEmptyList(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
            ]);
        $this->indexerProgressHandler->expects($this->once())
            ->method('start')
            ->with(0);
        $this->indexerProgressHandler->expects($this->once())
            ->method('finish');

        $this->indexer->index();
    }

    public function testIndexPreparesIndexing(): void
    {
        $this->finder->method('findAll')
            ->willReturn(['/a/b.php']);

        $this->indexService->expects($this->once())
            ->method('prepareIndexing')
            ->with(ResourceLanguage::default(), 'test-source');

        $this->indexer->index();
    }

    public function testIndexSkipResource(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php',
            ]);

        $this->updateResult->method('isSuccess')
            ->willReturn(true);

        $this->indexerFilter->method('accept')
            ->willReturnCallback(function (Resource $resource) {
                return ($resource->location !== '/a/b.php');
            });

        $this->updater->expects($this->exactly(1))
            ->method('addDocument');

        $this->updater->expects($this->exactly(1))
            ->method('update');

        $this->indexer->index();
    }

    public function testAborted(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php',
            ]);

        $this->aborter->method('isAbortionRequested')
            ->willReturn(true);

        $this->aborter->expects($this->once())
            ->method('resetAbortionRequest');

        $this->indexerProgressHandler->expects($this->once())
            ->method('abort');

        $this->indexer->index();
    }

    public function testWithUnsuccessfulStatus(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php',
            ]);

        $this->updateResult->method('isSuccess')
            ->willReturn(false);
        $this->updateResult->method('getErrorMessage')
            ->willReturn('index rejected the chunk');

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $this->indexer->index();
    }

    public function testWithInvalidResource(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
            ]);

        $this->resourceLoader->method('load')
            ->willThrowException(
                new InvalidResourceException(
                    ResourceLocation::of('/a/b.php'),
                ),
            );

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $this->indexer->index();
    }

    public function testUpdate(): void
    {
        $this->finder->method('findPaths')
            ->willReturn([
                '/a/b.php',
                '/a/c.php',
            ]);

        $this->updateResult->method('isSuccess')
            ->willReturn(true);

        $this->indexerFilter->method('accept')
            ->willReturn(true);

        $this->updater->expects($this->exactly(2))
            ->method('addDocument');

        $this->updater->expects($this->exactly(1))
            ->method('update');

        $this->indexer->update([
            '/a/b.php',
            '/a/c.php',
        ]);
    }

    public function testUpdateOtherLang(): void
    {
        $this->finder->method('findPaths')
            ->willReturn([
                '/a/b.php.translations/en_US.php',
            ]);

        $this->updateResult->method('isSuccess')
            ->willReturn(true);

        $this->indexerFilter->method('accept')
            ->willReturn(true);

        $this->updater->expects($this->exactly(1))
            ->method('addDocument');

        $this->updater->expects($this->exactly(1))
            ->method('update');

        $this->indexer->update([
            '/a/b.php.translations/en_US.php',
        ]);
    }

    public function testUpdateOtherLangWithLocParam(): void
    {
        $this->finder
            ->expects($this->once())
            ->method('findPaths')
            ->with([
                '/a/b.php.translations/en_US.php',
            ]);

        $this->indexer->update([
            '/a/b.php?loc=en_US',
        ]);
    }

    public function testUpdateOtherLangWithOtherParam(): void
    {
        $this->finder
            ->expects($this->once())
            ->method('findPaths')
            ->with([
                '/a/b.php',
            ]);

        $this->indexer->update([
            '/a/b.php?a=b',
        ]);
    }

    public function testUpdateWithParameter(): void
    {
        $this->finder->expects($this->once())
            ->method('findPaths')
            ->with($this->equalTo(['']))
            ->willReturn(['']);

        $this->indexer->update([
            '?a=b',
        ]);
    }

    public function testWithoutAvailableIndexes(): void
    {

        $this->availableIndexes = [];
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php',
                '/a/d.php',
                '/a/e.php',
                '/a/f.php',
                '/a/g.php',
                '/a/h.php',
                '/a/i.php',
                '/a/j.php',
                '/a/k.php',
                '/a/l.php',
            ]);

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $this->indexer->index();
    }

    public function testEnabledWithoutConfig(): void
    {
        $this->assertTrue(
            $this->indexer->enabled(),
            'an indexer that opted out of the config file should be enabled',
        );
    }

    public function testEnabledFollowsTheConfigFile(): void
    {
        $indexName = $this->createStub(IndexName::class);
        $indexName->method('name')->willReturn('test');

        $indexer = new InternalResourceIndexer(
            [ $this->documentEnricher ],
            $this->indexerFilter,
            $this->indexerProgressHandler,
            $this->finder,
            $this->resourceLoader,
            $this->indexService,
            $this->aborter,
            $this->indexerConfigurationLoader,
            'test-source',
            $indexName,
        );

        $this->indexerConfigurationLoader->method('exists')
            ->willReturn(false);

        $this->assertFalse(
            $indexer->enabled(),
            'without a CMS configuration file the indexer should not be '
            . 'offered',
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            'Indexer-Name',
            $this->indexer->getName(),
            'unexpected Indexer Name',
        );
    }

    public function testGetProgressHandler(): void
    {
        $this->assertEquals(
            $this->indexerProgressHandler,
            $this->indexer->getProgressHandler(),
            'unexpected progress handler',
        );
    }

    public function testSetProgressHandler(): void
    {
        $progressHandler = $this->createStub(IndexerProgressHandler::class);
        $this->indexer->setProgressHandler($progressHandler);
        $this->assertEquals(
            $progressHandler,
            $this->indexer->getProgressHandler(),
            'unexpected progress handler',
        );
    }

    public function testGetSource(): void
    {
        $this->assertEquals(
            'test-source',
            $this->indexer->getSource(),
            'unexpected source',
        );
    }

    public function testLock(): void
    {
        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Indexer is already running', [
                'index' => 'test-test-source',
            ]);
        $lock = $this->lockFactory->createLock('indexer.test-test-source');
        try {
            $lock->acquire();
            $this->indexer->index();
        } finally {
            $lock->release();
        }
    }

    public function testWithDifferentLocaleInResource(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/a.php',
                '/a/b.php',
                '/a/en.php',// will return a 'en_EN' Resource
            ]);
        $this->indexerFilter->method('accept')
            ->willReturn(true);

        // one for 'default' lang and one for resource internal 'en' locale
        $this->indexerProgressHandler->expects($this->exactly(2))
            ->method('advance');
        $this->updater->expects($this->exactly(3))
            ->method('addDocument');

        $this->indexer->index();
    }
}
