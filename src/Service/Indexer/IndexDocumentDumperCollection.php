<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use InvalidArgumentException;

class IndexDocumentDumperCollection
{
    /**
     * @param iterable<IndexDocumentDumper> $dumpers
     */
    public function __construct(
        private readonly iterable $dumpers,
    ) {}

    public function getDumper(string $id): IndexDocumentDumper
    {
        foreach ($this->dumpers as $dumper) {
            if ($dumper->getId() === $id) {
                return $dumper;
            }
        }
        throw new InvalidArgumentException(
            'Index document dumper not found for id: ' . $id,
        );
    }

    /**
     * @return array<IndexDocumentDumper>
     */
    public function getDumpers(): array
    {
        return $this->dumpers instanceof \Traversable
            ? iterator_to_array($this->dumpers)
            : $this->dumpers;
    }
}
