<?php

declare(strict_types=1);

namespace Atoolo\Index\Dto\Indexer;

use Atoolo\Resource\DataBag;

/**
 * @codeCoverageIgnore
 */
class IndexerConfiguration
{
    /**
     * @param string $source the id of the indexer, which names the file
     *    `configs/indexer/<id>.php`; see
     *    {@see \Atoolo\Index\Service\AbstractIndexer}
     */
    public function __construct(
        public readonly string $source,
        public readonly string $name,
        public readonly DataBag $data,
    ) {}
}
