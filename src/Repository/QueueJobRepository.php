<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Repository;

use Carbon\Carbon;
use WebFramework\Entity\QueueJob;

/**
 * @extends RepositoryCore<QueueJob>
 */
class QueueJobRepository extends RepositoryCore
{
    /** @var class-string<QueueJob> */
    protected static string $entityClass = QueueJob::class;

    public function getNextJob(string $queueName): ?QueueJob
    {
        $now = Carbon::now()->getTimestamp();

        $query = <<<'SQL'
SELECT id, queue_name, job_data, available_at, created_at, attempts
FROM jobs
WHERE queue_name = ? AND available_at <= ?
ORDER BY available_at ASC, id ASC
LIMIT 1
SQL;

        $params = [$queueName, $now];
        $result = $this->database->query($query, $params, 'Failed to retrieve next job');

        if ($result->RecordCount() === 0)
        {
            return null;
        }

        return $this->instantiateEntityFromData($result->fields);
    }

    public function countJobsInQueue(string $queueName): int
    {
        return $this->countObjects(['queue_name' => $queueName]);
    }

    public function clearQueue(string $queueName): void
    {
        $query = 'DELETE FROM jobs WHERE queue_name = ?';
        $params = [$queueName];

        $this->database->query($query, $params, 'Failed to clear queue');
    }
}
