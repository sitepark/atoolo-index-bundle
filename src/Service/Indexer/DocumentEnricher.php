<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\Resource;
use Atoolo\Index\Exception\DocumentEnrichingException;

/**
 * Implemented to fill an index document from a resource.
 *
 * An enricher is always written for one index target, because it sets the
 * fields or the structure that this target expects. Each target bundle
 * therefore collects its enricher under a tag of its own.
 *
 * @template T of IndexDocument
 */
interface DocumentEnricher
{
    /**
     * @template E of T
     * @param E $doc
     * @return E
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument;

    /**
     * Can be used, for example, to clear the loader's
     * cache if the loader uses a cache.
     */
    public function cleanup(): void;
}
