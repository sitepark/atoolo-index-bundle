<?php

declare(strict_types=1);

namespace Atoolo\Index\Service\Indexer;

use Atoolo\Index\Dto\Indexer\IndexerMessage;
use Atoolo\Index\Service\IndexName;
use Atoolo\Resource\ResourceLanguage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * One schedule for all indexers.
 *
 * `AddScheduleMessengerPass` creates one transport per schedule name, so one
 * provider per indexer would require one `messenger:consume` worker per
 * indexer. Therefore all indexers share a single schedule and are told apart
 * by the indexer id carried in the {@see IndexerMessage}.
 *
 * The lock guards the schedule itself. Concurrent runs of a single indexer
 * are already prevented by the indexer implementation.
 */
#[AsSchedule('atoolo_index')]
class IndexerScheduleProvider implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    /**
     * @param array<string,string> $schedules cron expression per indexer id
     */
    public function __construct(
        private readonly IndexerCollection $indexers,
        private readonly array $schedules,
        private readonly IndexName $indexName,
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore(),
        ),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getSchedule(): Schedule
    {
        if ($this->schedule !== null) {
            return $this->schedule;
        }

        $schedule = new Schedule();
        $knownIds = [];
        foreach ($this->indexers->getIndexers() as $indexer) {
            $knownIds[] = IndexerId::of($indexer);
        }

        foreach ($this->schedules as $id => $cron) {
            if (!in_array($id, $knownIds, true)) {
                $this->logger->warning(
                    'No indexer registered for scheduled id',
                    ['id' => $id],
                );
                continue;
            }
            $schedule->add(
                RecurringMessage::cron($cron, new IndexerMessage($id)),
            );
        }

        return $this->schedule = $schedule->lock(
            $this->lockFactory->createLock(
                'atoolo-index-scheduler-'
                . $this->indexName->name(ResourceLanguage::default()),
            ),
        );
    }
}
