<?php

declare(strict_types=1);

namespace Atoolo\Index\Test\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerMessage;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\IndexerScheduleProvider;
use Atoolo\Index\Service\IndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Trigger\TriggerInterface;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Lock\Store\SemaphoreStore;

#[CoversClass(IndexerScheduleProvider::class)]
class IndexerScheduleProviderTest extends TestCase
{
    public function testScheduleContainsMessagePerSource(): void
    {
        $provider = $this->createProvider(
            ['internal', 'genai'],
            ['internal' => '0 2 * * *', 'genai' => '0 3 * * *'],
        );

        $messages = $provider->getSchedule()->getRecurringMessages();
        $context = new MessageContext(
            'atoolo_index',
            'id',
            $this->createStub(TriggerInterface::class),
            new \DateTimeImmutable(),
        );

        $sources = [];
        foreach ($messages as $message) {
            foreach ($message->getProvider()->getMessages($context) as $payload) {
                $this->assertInstanceOf(IndexerMessage::class, $payload);
                $sources[] = $payload->source;
            }
        }

        $this->assertEquals(
            ['internal', 'genai'],
            $sources,
            'unexpected scheduled sources',
        );
    }

    public function testScheduleIsBuiltOnlyOnce(): void
    {
        $provider = $this->createProvider(['internal'], ['internal' => '0 2 * * *']);

        $this->assertSame(
            $provider->getSchedule(),
            $provider->getSchedule(),
            'schedule should be cached',
        );
    }

    public function testSkipsSourceWithoutIndexer(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning');

        $provider = $this->createProvider(
            ['internal'],
            ['unknown' => '0 2 * * *'],
            $logger,
        );

        $this->assertCount(
            0,
            $provider->getSchedule()->getRecurringMessages(),
            'unknown source should be skipped',
        );
    }

    /**
     * @param string[] $sources
     * @param array<string,string> $schedules
     */
    private function createProvider(
        array $sources,
        array $schedules,
        ?LoggerInterface $logger = null,
    ): IndexerScheduleProvider {
        $indexers = [];
        foreach ($sources as $source) {
            $indexer = $this->createStub(Indexer::class);
            $indexer->method('getSource')->willReturn($source);
            $indexers[] = $indexer;
        }

        $indexName = $this->createStub(IndexName::class);
        $indexName->method('name')->willReturn('test');

        return new IndexerScheduleProvider(
            new IndexerCollection($indexers),
            $schedules,
            $indexName,
            new LockFactory(new SemaphoreStore()),
            $logger ?? $this->createStub(LoggerInterface::class),
        );
    }
}
