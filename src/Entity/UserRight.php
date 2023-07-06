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
}
