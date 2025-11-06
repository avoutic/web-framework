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

        // Start transaction for atomic locking
        $this->database->startTransaction();

        try
        {
            // Use SELECT FOR UPDATE to lock the row atomically
            // Exclude jobs that have exceeded max_attempts
            $query = <<<'SQL'
SELECT id, queue_name, job_data, available_at, created_at, attempts, reserved_at, max_attempts
FROM jobs
WHERE queue_name = ? AND available_at <= ? AND reserved_at IS NULL AND attempts < max_attempts
ORDER BY available_at ASC, id ASC
LIMIT 1
FOR UPDATE
SQL;

            $params = [$queueName, $now];
            $result = $this->database->query($query, $params, 'Failed to retrieve next job');

            if ($result->RecordCount() === 0)
            {
                $this->database->commitTransaction();

                return null;
            }

            $queueJob = $this->instantiateEntityFromData($result->fields);

            // Atomically mark job as reserved
            $queueJob->setReservedAt($now);
            $this->save($queueJob);

            $this->database->commitTransaction();

            return $queueJob;
        }
        catch (\Throwable $e)
        {
            $this->database->rollbackTransaction();

            throw $e;
        }
    }

    public function countJobsInQueue(string $queueName): int
    {
        // Only count jobs that haven't exceeded max_attempts
        $query = 'SELECT COUNT(*) as count FROM jobs WHERE queue_name = ? AND attempts < max_attempts';
        $params = [$queueName];
        $result = $this->database->query($query, $params, 'Failed to count jobs in queue');

        if ($result->RecordCount() === 0)
        {
            return 0;
        }

        return (int) $result->fields['count'];
    }

    public function clearQueue(string $queueName): void
    {
        $query = 'DELETE FROM jobs WHERE queue_name = ?';
        $params = [$queueName];

        $this->database->query($query, $params, 'Failed to clear queue');
    }
}
