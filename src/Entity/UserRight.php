<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class UserRight extends EntityCore
{
    public static string $tableName = 'user_rights';
    public static array $baseFields = ['right_id', 'user_id'];

    private int $id;
    private int $rightId;
    private int $userId;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRightId(): int
    {
        return $this->rightId;
    }

    public function setRightId(int $rightId): void
    {
        $this->rightId = $rightId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}
