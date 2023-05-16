<?php

namespace WebFramework\Core\Security;

use WebFramework\Core\Database;

class DatabaseBlacklistService implements BlacklistService
{
    /**
     * @param array<mixed> $module_config
     */
    public function __construct(
        protected Database $database,
        protected array $module_config,
    ) {
    }

    public function cleanup(): void
    {
        $query = <<<'SQL'
        DELETE FROM blacklist_entries
        WHERE timestamp < ?
SQL;

        $cutoff = time() - $this->module_config['store_period'];

        $result = $this->database->query($query, [$cutoff]);
        if ($result === false)
        {
            throw new \RuntimeException('Failed to clean up blacklist entries');
        }
    }

    public function add_entry(string $ip, ?int $user_id, string $reason, int $severity = 1): void
    {
        // Auto cleanup old entries (Over 30 days old)
        //
        $this->cleanup();

        $full_reason = $reason;

        $entry = BlacklistEntry::create([
            'ip' => $ip,
            'user_id' => $user_id,
            'severity' => $severity,
            'reason' => $full_reason,
            'timestamp' => time(),
        ]);
    }

    public function is_blacklisted(string $ip, ?int $user_id): bool
    {
        $cutoff = time() - (int) $this->module_config['trigger_period'];
        $params = [$cutoff, $ip];
        $user_fmt = '';

        if ($user_id != null)
        {
            $params[] = $user_id;
            $user_fmt = 'OR user_id = ?';
        }

        $query = <<<SQL
        SELECT SUM(severity) AS total
        FROM blacklist_entries
        WHERE timestamp > ? AND
              (
                 ip = ?
                {$user_fmt}
              )
SQL;

        $result = $this->database->query($query, $params);

        if ($result === false)
        {
            throw new \RuntimeException('Failed to sum blacklist entries');
        }

        return $result->fields['total'] > $this->module_config['threshold'];
    }
}
