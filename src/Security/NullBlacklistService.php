<?php

namespace WebFramework\Security;

/**
 * Class NullBlacklistService.
 *
 * A null implementation of the BlacklistService interface.
 * This class is useful for testing or when blacklisting is not required.
 */
class NullBlacklistService implements BlacklistService
{
    /**
     * Add an entry to the blacklist.
     *
     * @param string   $ip       The IP address to blacklist
     * @param null|int $userId   The user ID associated with the blacklist entry (if any)
     * @param string   $reason   The reason for blacklisting
     * @param int      $severity The severity of the blacklist entry
     */
    public function addEntry(string $ip, ?int $userId, string $reason, int $severity = 1): void {}

    /**
     * Check if an IP address or user ID is blacklisted.
     *
     * @param string   $ip     The IP address to check
     * @param null|int $userId The user ID to check (if any)
     *
     * @return bool Always returns false
     */
    public function isBlacklisted(string $ip, ?int $userId): bool
    {
        return false;
    }
}
