<?php

namespace WebFramework\Entity;

use WebFramework\Core\EntityCore;

class BlacklistEntry extends EntityCore
{
    public static string $tableName = 'blacklist_entries';
    public static array $baseFields = ['ip', 'user_id', 'severity', 'reason', 'timestamp'];

    private int $id;
    private string $ip;
    private ?int $userId;
    private int $severity;
    private string $reason;
    private int $timestamp;
}
