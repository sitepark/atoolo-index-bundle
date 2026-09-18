<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Resource\Resource;

interface ResourceFilter
{
    public function accept(Resource $resource): bool;
}
