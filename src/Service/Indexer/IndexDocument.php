<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

/**
 * Represents a document in the index.
 * The fields are different depending on the index target and its schema.
 * The interface is implemented per target.
 */
interface IndexDocument
{
    /**
     * The raw field data of the document. Each target returns its own
     * structure here - Solr field names for a Solr document, the JSON keys
     * of the remote API for a http based target.
     *
     * @return array<string,mixed>
     */
    public function getFields(): array;
}
