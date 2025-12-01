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

    public function getNextJob(string $queueName, int $reservationTimeout = 300): ?QueueJob
    {
        $now = Carbon::now()->getTimestamp();
        $staleThreshold = $now - $reservationTimeout;

        // Start transaction for atomic locking
        $this->database->startTransaction();

        try
        {
            // Use SELECT FOR UPDATE to lock the row atomically, and skip already locked rows.
            // Include jobs that are:
            // 1. Not reserved (reserved_at IS NULL), OR
            // 2. Reserved but stale (reserved_at < staleThreshold) - for crash recovery
            // Exclude jobs that have exceeded max_attempts
            $query = <<<'SQL'
SELECT *
FROM jobs
WHERE queue_name = ?
  AND available_at <= ?
  AND attempts < max_attempts
  AND (reserved_at IS NULL OR reserved_at < ?)
  AND completed_at IS NULL
ORDER BY available_at ASC, id ASC
LIMIT 1
FOR UPDATE SKIP LOCKED
SQL;

            $params = [$queueName, $now, $staleThreshold];
            $result = $this->database->query($query, $params, 'Failed to retrieve next job');

            if ($result->RecordCount() === 0)
            {
                $this->database->commitTransaction();

                return null;
            }

            $queueJob = $this->instantiateEntityFromData($result->fields);
            $newAttempts = $queueJob->getAttempts() + 1;

            $queueJob->setAttempts($newAttempts);

            // If this is a stale job (was reserved but timed out), release it first
            // This handles worker crash recovery - jobs that were locked but never completed
            if ($queueJob->getReservedAt() !== null)
            {
                // Check if incrementing attempts would exceed max_attempts
                // If so, mark as failed and move to dead letter queue instead of retrying
                if ($newAttempts > $queueJob->getMaxAttempts())
                {
                    $queueJob->setFailedAt(Carbon::now()->getTimestamp());
                    $deadLetterQueueName = $queueName.'-failed';
                    $queueJob->setQueueName($deadLetterQueueName);

                    $this->save($queueJob);
                    $this->database->commitTransaction();

                    return null;
                }

                // Clear error field on recovery retry
                $queueJob->setError(null);
            }

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
        $query = 'SELECT COUNT(*) as count FROM jobs WHERE queue_name = ? AND attempts < max_attempts AND completed_at IS NULL';
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
