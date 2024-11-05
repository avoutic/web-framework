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

use WebFramework\Core\EntityCore;

/**
 * Represents a blacklist entry in the system.
 */
class BlacklistEntry extends EntityCore
{
    protected static string $tableName = 'blacklist_entries';
    protected static array $baseFields = ['ip', 'user_id', 'severity', 'reason', 'timestamp'];

    private int $id;
    private string $ip = '';
    private ?int $userId = null;
    private int $severity = 0;
    private string $reason = '';
    private int $timestamp = 0;

    /**
     * Get the blacklist entry ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the blacklist entry ID.
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the IP address.
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Set the IP address.
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * Get the associated user ID.
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Set the associated user ID.
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * Get the severity level.
     */
    public function getSeverity(): int
    {
        return $this->severity;
    }

    /**
     * Set the severity level.
     */
    public function setSeverity(int $severity): void
    {
        $this->severity = $severity;
    }

    /**
     * Get the reason for blacklisting.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Set the reason for blacklisting.
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    /**
     * Get the timestamp of the blacklist entry.
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Set the timestamp of the blacklist entry.
     */
    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }
}
