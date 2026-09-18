<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use JsonSerializable;

/**
 * Represents a document in the index.
 *
 * The interface deliberately prescribes no structure. A document only has to
 * be able to represent itself as data, so that `index:dump-document` can show
 * what an index run would write. How that data looks is the target's
 * business: a flat map of fields for a schema based index, a nested tree, a
 * list of sections - whatever the target sends.
 *
 * How the document reaches the index is the target's business as well. The
 * indexer never looks inside it; it creates one through the target's
 * {@see IndexDocumentFactory}, passes it through the target's
 * {@see DocumentEnricher} list and hands it back to the target's
 * {@see IndexUpdater}.
 */
interface IndexDocument extends JsonSerializable {}
