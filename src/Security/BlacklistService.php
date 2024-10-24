<?php

namespace WebFramework\Security;

/**
 * Interface BlacklistService.
 *
 * Defines the contract for blacklist services in the WebFramework.
 */
interface BlacklistService
{
    /**
     * Add an entry to the blacklist.
     *
     * @param string   $ip       The IP address to blacklist
     * @param null|int $userId   The user ID associated with the blacklist entry (if any)
     * @param string   $reason   The reason for blacklisting
     * @param int      $severity The severity of the blacklist entry
     */
    public function addEntry(string $ip, ?int $userId, string $reason, int $severity = 1): void;

    /**
     * Check if an IP address or user ID is blacklisted.
     *
     * @param string   $ip     The IP address to check
     * @param null|int $userId The user ID to check (if any)
     *
     * @return bool True if the IP or user is blacklisted, false otherwise
     */
    public function isBlacklisted(string $ip, ?int $userId): bool;
}
