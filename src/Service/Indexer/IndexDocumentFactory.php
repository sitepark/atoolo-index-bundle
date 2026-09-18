<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

/**
 * Creates the index document of a specific index target.
 *
 * The same factory is used by the {@see IndexUpdater} of a target and by its
 * {@see IndexDocumentDumper}, so that a dump and an index run always produce
 * the same document.
 */
interface IndexDocumentFactory
{
    public function create(): IndexDocument;
}
