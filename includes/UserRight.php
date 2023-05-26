<?php

namespace WebFramework\Core;

class UserRight extends DataCore
{
    protected static string $tableName = 'user_rights';
    protected static array $baseFields = ['right_id', 'user_id'];

    public function getRight(): Right|false
    {
        return Right::getObjectById($this->rightId);
    }
}
