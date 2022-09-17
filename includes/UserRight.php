<?php
namespace WebFramework\Core;

class UserRight extends DataCore
{
    static protected string $table_name = 'user_rights';
    static protected array $base_fields = array('right_id', 'user_id');

    public function get_right(): Right|false
    {
        return Right::get_object_by_id($this->right_id);
    }
};
?>
