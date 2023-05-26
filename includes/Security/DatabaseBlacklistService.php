<?php

namespace WebFramework\Security;

use WebFramework\Core\Database;

class DatabaseBlacklistService implements BlacklistService
{
    public function __construct(
        private Database $database,
        private int $storePeriod,
        private int $threshold,
        private int $triggerPeriod,
    ) {
    }

    public function cleanup(): void
    {
        $query = <<<'SQL'
        DELETE FROM blacklist_entries
        WHERE timestamp < ?
SQL;

        $cutoff = time() - $this->storePeriod;

        $result = $this->database->query($query, [$cutoff]);
        if ($result === false)
        {
            throw new \RuntimeException('Failed to clean up blacklist entries');
        }
    }

    public function addEntry(string $ip, ?int $userId, string $reason, int $severity = 1): void
    {
        $fullReason = $reason;

        $entry = BlacklistEntry::create([
            'ip' => $ip,
            'user_id' => $userId,
            'severity' => $severity,
            'reason' => $fullReason,
            'timestamp' => time(),
        ]);
    }

    public function isBlacklisted(string $ip, ?int $userId): bool
    {
        $cutoff = time() - $this->triggerPeriod;
        $params = [$cutoff, $ip];
        $userFmt = '';

        if ($userId != null)
        {
            $params[] = $userId;
            $userFmt = 'OR user_id = ?';
        }

        $query = <<<SQL
        SELECT SUM(severity) AS total
        FROM blacklist_entries
        WHERE timestamp > ? AND
              (
                 ip = ?
                {$userFmt}
              )
SQL;

        $result = $this->database->query($query, $params);

        if ($result === false)
        {
            throw new \RuntimeException('Failed to sum blacklist entries');
        }

        return $result->fields['total'] > $this->threshold;
    }
}
