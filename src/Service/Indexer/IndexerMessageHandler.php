<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerMessage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class IndexerMessageHandler
{
    public function __construct(
        private readonly IndexerCollection $indexers,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(IndexerMessage $message): void
    {
        try {
            $indexer = $this->indexers->getIndexer($message->source);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'No indexer found for scheduled source',
                ['source' => $message->source, 'exception' => $e],
            );
            return;
        }

        $status = $indexer->index();
        $this->logger->info(
            'indexer finish: ' . $status->getStatusLine(),
            ['source' => $message->source],
        );
    }
}
