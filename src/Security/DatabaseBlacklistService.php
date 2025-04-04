<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use Carbon\Carbon;
use WebFramework\Core\Database;
use WebFramework\Repository\BlacklistEntryRepository;

/**
 * Class DatabaseBlacklistService.
 *
 * Implements the BlacklistService interface using a database for storage.
 */
class DatabaseBlacklistService implements BlacklistService
{
    /**
     * DatabaseBlacklistService constructor.
     *
     * @param Database                 $database                 The database service
     * @param BlacklistEntryRepository $blacklistEntryRepository The blacklist entry repository
     * @param int                      $storePeriod              The period to store blacklist entries (in seconds)
     * @param int                      $threshold                The threshold for blacklisting
     * @param int                      $triggerPeriod            The period to consider for blacklisting (in seconds)
     */
    public function __construct(
        private Database $database,
        private BlacklistEntryRepository $blacklistEntryRepository,
        private int $storePeriod,
        private int $threshold,
        private int $triggerPeriod,
    ) {}

    /**
     * Perform cleanup of expired blacklist entries.
     */
    public function cleanup(): void
    {
        $query = <<<'SQL'
        DELETE FROM blacklist_entries
        WHERE timestamp < ?
SQL;

        $cutoff = Carbon::now()->subSeconds($this->storePeriod)->getTimestamp();

        $result = $this->database->query($query, [$cutoff], 'Failed to clean up blacklist entries');
    }

    /**
     * Add an entry to the blacklist.
     *
     * @param string   $ip       The IP address to blacklist
     * @param null|int $userId   The user ID associated with the blacklist entry (if any)
     * @param string   $reason   The reason for blacklisting
     * @param int      $severity The severity of the blacklist entry
     */
    public function addEntry(string $ip, ?int $userId, string $reason, int $severity = 1): void
    {
        $fullReason = $reason;

        $entry = $this->blacklistEntryRepository->create([
            'ip' => $ip,
            'user_id' => $userId,
            'severity' => $severity,
            'reason' => $fullReason,
            'timestamp' => Carbon::now()->getTimestamp(),
        ]);
    }

    /**
     * Check if an IP address or user ID is blacklisted.
     *
     * @param string   $ip     The IP address to check
     * @param null|int $userId The user ID to check (if any)
     *
     * @return bool True if the IP or user is blacklisted, false otherwise
     */
    public function isBlacklisted(string $ip, ?int $userId): bool
    {
        $cutoff = Carbon::now()->subSeconds($this->triggerPeriod)->getTimestamp();
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

        $result = $this->database->query($query, $params, 'Failed to sum blacklist entries');

        return $result->fields['total'] > $this->threshold;
    }
}
