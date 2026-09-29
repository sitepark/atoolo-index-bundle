<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Index\Indexer;
use Atoolo\Index\Service\AbstractIndexer;

/**
 * The id of any indexer, see {@see AbstractIndexer} for how it differs
 * from the source.
 *
 * The {@see Indexer} interface gains `getId()` only with the next major
 * version. Until then an indexer that implements the interface directly
 * has no id of its own and is known by its source, as before.
 */
final class IndexerId
{
    private function __construct() {}

    public static function of(Indexer $indexer): string
    {
        return $indexer instanceof AbstractIndexer
            ? $indexer->getId()
            : $indexer->getSource();
    }

    /**
     * How the console names an indexer: by its source, and by its id only
     * where the two differ.
     */
    public static function label(string $id, string $source): string
    {
        return $id === $source
            ? '(source: ' . $source . ')'
            : '(id: ' . $id . ', source: ' . $source . ')';
    }
}
