<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Entity;

class QueueJob extends EntityCore
{
    protected static string $tableName = 'jobs';
    protected static array $baseFields = [
        'queue_name',
        'job_data',
        'available_at',
        'created_at',
        'attempts',
    ];

    protected int $id;
    protected string $queueName;
    protected string $jobData;
    protected int $availableAt;
    protected string $createdAt;
    protected int $attempts;

    public function getId(): int
    {
        return $this->id;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function setQueueName(string $queueName): void
    {
        $this->queueName = $queueName;
    }

    public function getJobData(): string
    {
        return $this->jobData;
    }

    public function setJobData(string $jobData): void
    {
        $this->jobData = $jobData;
    }

    public function getAvailableAt(): int
    {
        return $this->availableAt;
    }

    public function setAvailableAt(int $availableAt): void
    {
        $this->availableAt = $availableAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }
}
