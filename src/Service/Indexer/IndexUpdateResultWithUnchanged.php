<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

/**
 * A result that also reports the documents the target left as they were.
 *
 * A target that recognises an unchanged document - the GenAI application by
 * a content hash, so that it does not embed it again - still receives it on
 * every full run, because the document has to take over the process id of
 * the run, and reports it here. The indexer shows the number as `unchanged`
 * in the status. An unchanged document is no error.
 *
 * Kept apart from {@see IndexUpdateResult}, so that a target that cannot
 * tell does not have to implement it.
 */
interface IndexUpdateResultWithUnchanged extends IndexUpdateResult
{
    public function getUnchanged(): int;
}
