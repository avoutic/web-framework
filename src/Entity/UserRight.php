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

/**
 * Represents a user-right association in the system.
 */
class UserRight extends EntityCore
{
    protected static string $tableName = 'user_rights';
    protected static array $baseFields = ['right_id', 'user_id'];

    private int $id;
    private int $rightId;
    private int $userId;

    /**
     * Get the user-right association ID.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the associated right ID.
     */
    public function getRightId(): int
    {
        return $this->rightId;
    }

    /**
     * Set the associated right ID.
     */
    public function setRightId(int $rightId): void
    {
        $this->rightId = $rightId;
    }

    /**
     * Get the associated user ID.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * Set the associated user ID.
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}
