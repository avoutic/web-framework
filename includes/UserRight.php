<?php

namespace WebFramework\Core;

class UserRight extends DataCore
{
    protected static string $table_name = 'user_rights';
    protected static array $base_fields = ['right_id', 'user_id'];

    public function get_right(): Right|false
    {
        return Right::get_object_by_id($this->right_id);
    }
}
