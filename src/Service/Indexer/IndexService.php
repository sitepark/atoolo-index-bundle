<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;

/**
 * Backend agnostic view of the index an indexer writes to.
 *
 * Every index target (Solr, a GenAI application, ...) provides its own
 * implementation. Everything that is specific to a single target - like
 * Solr's delete-by-query - is intentionally not part of this interface.
 */
interface IndexService
{
    public function getIndex(ResourceLanguage $lang): string;

    /**
     * @return string[]
     */
    public function getManagedIndices(): array;

    public function updater(ResourceLanguage $lang): IndexUpdater;

    /**
     * Hook that is called before a full index run. Solr uses it to delete
     * its error protocol, other targets can leave it a no-op.
     */
    public function prepareIndexing(
        ResourceLanguage $lang,
        string $source,
    ): void;

    public function deleteExcludingProcessId(
        ResourceLanguage $lang,
        string $source,
        string $processId,
    ): void;

    /**
     * @param string[] $idList
     */
    public function deleteByIdListForAllLanguages(
        string $source,
        array $idList,
    ): void;

    public function commit(ResourceLanguage $lang): void;

    public function commitForAllLanguages(): void;
}
