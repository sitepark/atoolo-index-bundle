<?php

declare(strict_types=1);

namespace Atoolo\Index\Service;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Index\Exception\UnsupportedIndexLanguageException;

/**
 * The name of the index a channel writes to.
 *
 * Whether a channel has one index or several is up to the target: a target
 * that treats every language separately needs one per language, a target
 * whose model is multilingual needs a single one. A target that disagrees
 * with the implementation this bundle ships brings its own.
 */
interface IndexName
{
    /**
     * @throws UnsupportedIndexLanguageException Is thrown if no valid index
     *  can be determined for the language.
     */
    public function name(ResourceLanguage $lang): string;

    /**
     * Every index name of this channel. A target with a single index returns
     * exactly one entry.
     *
     * @return string[]
     */
    public function names(): array;
}
