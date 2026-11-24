<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use WebFramework\Database\Database;
use WebFramework\Database\DatabaseResultWrapper;
use WebFramework\Repository\QueueJobRepository;

/**
 * @internal
 *
 * @covers \WebFramework\Repository\QueueJobRepository
 */
final class QueueJobRepositoryStatsTest extends Unit
{
    /** @var array<array{query: string, params: array<mixed>}> */
    private array $queries = [];

    private function buildRepository(mixed $aggregate): QueueJobRepository
    {
        $this->queries = [];

        $database = $this->makeEmpty(Database::class, [
            'query' => function (string $query, array $params) use ($aggregate) {
                $this->queries[] = ['query' => (string) preg_replace('/\s+/', ' ', $query), 'params' => $params];

                $result = $this->makeEmpty(DatabaseResultWrapper::class, ['RecordCount' => 1]);
                $result->fields = ['aggregate' => $aggregate];

                return $result;
            },
        ]);

        return $this->construct(QueueJobRepository::class, [$this->makeEmpty(Container::class), $database]);
    }

    public function testCountDeadLetterJobsCountsTheFailedQueueWithoutLookingAtAttempts()
    {
        $repository = $this->buildRepository('4');

        verify($repository->countDeadLetterJobs('mail'))->same(4);
        verify($this->queries[0]['query'])->stringContainsString('COUNT(');
        verify($this->queries[0]['query'])->stringNotContainsString('attempts');
        verify($this->queries[0]['params'])->same(['mail-failed']);
    }

    public function testOldestPendingTimestamp()
    {
        $repository = $this->buildRepository('1735732800');

        verify($repository->getOldestPendingTimestamp('mail'))->same(1735732800);
        verify($this->queries[0]['query'])->stringContainsString('MIN(');
        verify($this->queries[0]['query'])->stringContainsString('created_at');
        verify($this->queries[0]['query'])->stringContainsString('max_attempts');
        verify($this->queries[0]['params'])->same(['mail']);
    }

    public function testOldestPendingTimestampIsNullForAnEmptyQueue()
    {
        verify($this->buildRepository(null)->getOldestPendingTimestamp('mail'))->null();
    }
}
