<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Atoolo\Index\Indexer;

/**
 * An indexer that can index single resource paths instead of the whole
 * resource tree.
 */
interface UpdatableIndexer extends Indexer
{
    /**
     * @param string[] $paths
     */
    public function update(array $paths): IndexerStatus;
}
