<?php
namespace WebFramework\Core;

class Right extends DataCore
{
    static protected string $table_name = 'rights';
    static protected array $base_fields = array('short_name', 'name');
};
?>
