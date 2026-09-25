<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\Change\ResourceChangeDeferredException;
use Atoolo\Resource\Change\ResourceChangeHandler;
use Atoolo\Resource\Change\ResourceChanges;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Brings the resources the CMS has created, changed or removed into every
 * enabled index - the Solr index of the search as well as a GenAI
 * application, for example.
 *
 * While a full run of an indexer is in progress, the changes are deferred
 * for all indexers, see {@see InternalResourceIndexer::isIndexing()}. The
 * indexers already served then see the changes once more, which is
 * harmless, since indexing is idempotent.
 */
class IndexerResourceChangeHandler implements ResourceChangeHandler
{
    public function __construct(
        private readonly IndexerCollection $indexers,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function handle(ResourceChanges $changes): void
    {
        $indexers = array_values(array_filter(
            $this->indexers->getIndexers(),
            static fn($indexer) => $indexer->enabled(),
        ));

        foreach ($indexers as $indexer) {
            if (
                $indexer instanceof InternalResourceIndexer
                && $indexer->isIndexing()
            ) {
                throw new ResourceChangeDeferredException(
                    'indexer ' . $indexer->getSource() . ' is running',
                );
            }
        }

        $paths = $changes->changedPaths();
        foreach ($indexers as $indexer) {
            if (!empty($paths) && $indexer instanceof UpdatableIndexer) {
                $status = $indexer->update($paths);
                $this->logger->info('Resources updated', [
                    'source' => $indexer->getSource(),
                    'paths' => count($paths),
                    'status' => $status->getStatusLine(),
                ]);
            }
            if (!empty($changes->removedIds)) {
                $indexer->remove($changes->removedIds);
                $this->logger->info('Resources removed', [
                    'source' => $indexer->getSource(),
                    'ids' => count($changes->removedIds),
                ]);
            }
        }
    }
}
