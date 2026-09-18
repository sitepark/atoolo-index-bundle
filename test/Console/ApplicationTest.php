<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Console;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Index\Console\Application;
use Atoolo\Index\Console\Command\Indexer;
use Atoolo\Index\Console\Command\Io\IndexerProgressBar;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\InternalResourceIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
class ApplicationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstruct(): void
    {
        $resourceChannel = $this->createStub(
            ResourceChannel::class,
        );
        $indexer = $this->createStub(
            InternalResourceIndexer::class,
        );
        $indexers = new IndexerCollection([$indexer]);
        $progressBar = $this->createStub(IndexerProgressBar::class);
        $application = new Application([
            new Indexer($resourceChannel, $progressBar, $indexers),
        ]);
        $command = $application->get('index:indexer');
        $this->assertInstanceOf(
            Indexer::class,
            $command,
            'unexpected indexer command',
        );
    }
}
