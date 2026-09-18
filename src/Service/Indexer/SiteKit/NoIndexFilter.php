<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Index\Service\Indexer\ResourceFilter;

class NoIndexFilter implements ResourceFilter
{
    public function accept(Resource $resource): bool
    {
        $noIndex = $resource->data->getBool('noIndex');
        return $noIndex !== true;
    }
}
