<?php

namespace WebFramework\Security;

use WebFramework\Core\DataCore;

class BlacklistEntry extends DataCore
{
    protected static string $tableName = 'blacklist_entries';
    protected static array $baseFields = ['ip', 'user_id', 'severity', 'reason', 'timestamp'];
}
