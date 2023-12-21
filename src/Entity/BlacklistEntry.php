<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class BlacklistEntry extends EntityCore
{
    protected static string $tableName = 'blacklist_entries';
    protected static array $baseFields = ['ip', 'user_id', 'severity', 'reason', 'timestamp'];

    private int $id;
    private string $ip;
    private ?int $userId;
    private int $severity;
    private string $reason;
    private int $timestamp;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getSeverity(): int
    {
        return $this->severity;
    }

    public function setSeverity(int $severity): void
    {
        $this->severity = $severity;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
