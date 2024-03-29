<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class Session extends EntityCore
{
    protected static string $tableName = 'sessions';
    protected static array $baseFields = ['user_id', 'session_id', 'start', 'last_active'];

    private int $id;
    private int $userId;
    private string $sessionId = '';
    private string $start = '';
    private string $lastActive = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getStart(): string
    {
        return $this->start;
    }

    public function setStart(string $start): void
    {
        $this->start = $start;
    }

    public function getLastActive(): string
    {
        return $this->lastActive;
    }

    public function setLastActive(string $lastActive): void
    {
        $this->lastActive = $lastActive;
    }
}
