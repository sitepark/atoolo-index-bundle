<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Index\Indexer;
use InvalidArgumentException;

class IndexerCollection
{
    /**
     * @param iterable<Indexer> $indexers
     */
    public function __construct(
        private readonly iterable $indexers,
    ) {}

    public function getIndexer(string $id): Indexer
    {
        foreach ($this->indexers as $indexer) {
            if (IndexerId::of($indexer) === $id) {
                return $indexer;
            }
        }
        throw new InvalidArgumentException(
            'Indexer not found for id: ' . $id,
        );
    }

    /**
     * @return array<Indexer>
     */
    public function getIndexers(): array
    {
        return $this->indexers instanceof \Traversable
            ? iterator_to_array($this->indexers)
            : $this->indexers;
    }
}
