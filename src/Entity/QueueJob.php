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
        'reserved_at',
        'max_attempts',
        'error',
        'failed_at',
    ];

    protected int $id;
    protected string $queueName;
    protected string $jobData;
    protected int $availableAt;
    protected string $createdAt;
    protected int $attempts;
    protected ?int $reservedAt = null;
    protected int $maxAttempts;
    protected ?string $error = null;
    protected ?int $failedAt = null;

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

    public function getReservedAt(): ?int
    {
        return $this->reservedAt;
    }

    public function setReservedAt(?int $reservedAt): void
    {
        $this->reservedAt = $reservedAt;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(int $maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): void
    {
        $this->error = $error;
    }

    public function getFailedAt(): ?int
    {
        return $this->failedAt;
    }

    public function setFailedAt(?int $failedAt): void
    {
        $this->failedAt = $failedAt;
    }
}
