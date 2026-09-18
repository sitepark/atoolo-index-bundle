<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

interface IndexUpdateResult
{
    public function isSuccess(): bool;

    public function getErrorMessage(): ?string;
}
