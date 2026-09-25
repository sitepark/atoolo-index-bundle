<?php

declare(strict_types=1);

namespace Atoolo\Index\Dto\Indexer;

/**
 * Message of the generic indexer schedule. It names the indexer to be run.
 *
 * `$source` holds the id of the indexer, see
 * {@see \Atoolo\Index\Service\Indexer\IndexerId}; it keeps its name
 * until the next major version, so that queued messages stay readable.
 *
 * @codeCoverageIgnore
 */
class IndexerMessage
{
    public function __construct(
        public readonly string $source,
    ) {}
}
