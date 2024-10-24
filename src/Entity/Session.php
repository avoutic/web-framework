<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

/**
 * Represents a user session in the system.
 */
class Session extends EntityCore
{
    protected static string $tableName = 'sessions';
    protected static array $baseFields = ['user_id', 'session_id', 'start', 'last_active'];

    private int $id;
    private int $userId;
    private string $sessionId = '';
    private string $start = '';
    private string $lastActive = '';

    /**
     * Get the session ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the user ID associated with this session.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the user ID associated with this session.
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get the session identifier.
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Set the session identifier.
     */
    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Get the start time of the session.
     */
    public function getStart(): string
    {
        return $this->start;
    }

    /**
     * Set the start time of the session.
     */
    public function setStart(string $start): void
    {
        $this->start = $start;
    }

    /**
     * Get the last active time of the session.
     */
    public function getLastActive(): string
    {
        return $this->lastActive;
    }

    /**
     * Set the last active time of the session.
     */
    public function setLastActive(string $lastActive): void
    {
        $this->lastActive = $lastActive;
    }
}
