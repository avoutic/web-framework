<?php
namespace WebFramework\Core;

class BlacklistEntry extends DataCore
{
    static protected string $table_name = 'blacklist_entries';
    static protected array $base_fields = array('ip', 'user_id', 'severity', 'reason', 'timestamp');
};
?>
