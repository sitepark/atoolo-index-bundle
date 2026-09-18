<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;

/**
 * Backend agnostic view of the index an indexer writes to.
 *
 * Every index target - Solr, a GenAI application, whatever comes next -
 * provides its own implementation. The interface carries only what a run of
 * the indexer needs; everything a target can do beyond that stays with the
 * target. Solr's free-form delete-by-query is the example: the two deletions
 * below are the ones the indexer lifecycle needs, and a target implements
 * them however it likes.
 */
interface IndexService
{
    public function getIndex(ResourceLanguage $lang): string;

    /**
     * The indices of this channel the target actually holds. The indexer
     * skips resources whose index is not among them and reports that as an
     * error instead of writing into nothing.
     *
     * @return string[]
     */
    public function getManagedIndices(): array;

    public function updater(ResourceLanguage $lang): IndexUpdater;

    /**
     * Hook that is called before a full index run, for whatever a target has
     * to put in order first. Solr deletes the error protocol of the previous
     * run here; a target with nothing to do leaves it empty.
     */
    public function prepareIndexing(
        ResourceLanguage $lang,
        string $source,
    ): void;

    /**
     * Removes what this run did not touch: everything of the given source
     * that does not carry the given process id.
     *
     * This is the one thing the interface expects of a document - that it
     * keeps the process id of the run that wrote it. Without it a full run
     * cannot tell stale documents from current ones.
     */
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

    /**
     * Makes everything that was transferred take effect. A target that needs
     * no such step leaves it empty.
     */
    public function commit(ResourceLanguage $lang): void;

    public function commitForAllLanguages(): void;
}
