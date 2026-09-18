<?php

declare(strict_types=1);

namespace Atoolo\Index\Service;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Index\Exception\UnsupportedIndexLanguageException;

interface IndexName
{
    /**
     * @throws UnsupportedIndexLanguageException Is thrown if no valid index
     *  can be determined for the language.
     */
    public function name(ResourceLanguage $lang): string;

    /**
     * The returned list contains the default index name and the index
     * name of all language-specific indexes.
     *
     * @return string[]
     */
    public function names(): array;
}
