<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

/**
 * Collects the documents of one chunk and transfers them to the index.
 */
interface IndexUpdater
{
    public function createDocument(): IndexDocument;

    public function addDocument(IndexDocument $document): void;

    public function clearDocuments(): void;

    public function update(): IndexUpdateResult;
}
