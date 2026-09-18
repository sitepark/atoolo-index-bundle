<?php

declare(strict_types=1);

namespace Atoolo\Index;

use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Atoolo\Index\Service\Indexer\IndexerProgressHandler;

/**
 * The service interface for filling an index.
 *
 * The main task of an indexer is to systematically analyze documents or
 * content in order to extract relevant information from them. That
 * information is structured and handed to an index target, which stores it in
 * whatever form it needs to answer requests quickly. Where the content comes
 * from is the indexer's business, what happens to it afterwards the target's.
 */
interface Indexer
{
    public function getName(): string;

    public function getSource(): string;

    public function getProgressHandler(): IndexerProgressHandler;

    public function setProgressHandler(
        IndexerProgressHandler $progressHandler,
    ): void;

    public function index(): IndexerStatus;

    public function abort(): void;

    public function enabled(): bool;

    /**
     * @param string[] $idList
     */
    public function remove(array $idList): void;
}
