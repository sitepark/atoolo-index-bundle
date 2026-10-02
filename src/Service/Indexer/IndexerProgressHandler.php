<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Throwable;

interface IndexerProgressHandler
{
    public function prepare(string $message): void;
    public function start(int $total): void;
    public function startUpdate(int $total): void;
    public function advance(int $step): void;
    public function skip(int $step): void;
    /**
     * Documents the target received but left as they were, see
     * {@see IndexUpdateResultWithUnchanged}. They are counted by
     * {@see advance()} already.
     */
    public function unchanged(int $step): void;
    public function error(Throwable $throwable): void;
    public function finish(): void;
    public function abort(): void;

    public function getStatus(): IndexerStatus;
}
