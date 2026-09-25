<?php

declare(strict_types=1);

namespace Atoolo\Index\Service;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Index\Dto\Indexer\IndexerConfiguration;
use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Index\Service\Indexer\IndexerId;
use Atoolo\Index\Service\Indexer\IndexerProgressHandler;
use Atoolo\Index\Service\Indexer\IndexingAborter;

/**
 * An indexer has an id and a source, which are two different things.
 *
 * The source names where the content comes from and is handed to the index
 * target with every document. Indexers that read the same content into
 * different targets share it - the solr and the GenAI indexer both index
 * the `internal` resources.
 *
 * The id names the indexer itself and has to be unique: it selects the
 * indexer on the console and in the schedule, names its configuration
 * file `configs/indexer/<id>.php` and keys its status and abortion. It
 * defaults to the source, so an indexer that is the only one of its source
 * needs none.
 */
abstract class AbstractIndexer implements Indexer
{
    private IndexerConfiguration $config;

    protected readonly string $id;

    public function __construct(
        protected readonly IndexName $indexName,
        protected IndexerProgressHandler $progressHandler,
        protected readonly IndexingAborter $aborter,
        protected readonly IndexerConfigurationLoader $configLoader,
        protected readonly string $source,
        ?string $id = null,
    ) {
        $this->id = $id ?? $source;
    }

    protected function getKey(): string
    {
        return $this->indexName->name(ResourceLanguage::default())
            . '-' . $this->id;
    }

    protected function getConfig(): IndexerConfiguration
    {
        return $this->config ??= $this->configLoader->load($this->id);
    }

    /**
     * Part of the {@see Indexer} interface with the next major version;
     * until then use {@see IndexerId::of()} for any indexer.
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->getConfig()->name;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getProgressHandler(): IndexerProgressHandler
    {
        return $this->progressHandler;
    }

    public function setProgressHandler(
        IndexerProgressHandler $progressHandler,
    ): void {
        $this->progressHandler = $progressHandler;
    }

    public function abort(): void
    {
        $this->aborter->requestAbortion($this->getKey());
    }

    protected function isAbortionRequested(): bool
    {
        return $this->aborter->isAbortionRequested($this->getKey());
    }

    public function enabled(): bool
    {
        return $this->configLoader->exists($this->id);
    }

    abstract public function index(): IndexerStatus;

    /**
     * @inheritDoc
     */
    abstract public function remove(array $idList): void;
}
