<?php

declare(strict_types=1);

namespace Atoolo\Index\Dto\Indexer;

/**
 * Message of the generic indexer schedule. It names the source whose
 * indexer is to be run.
 *
 * @codeCoverageIgnore
 */
class IndexerMessage
{
    public function __construct(
        public readonly string $source,
    ) {}
}
