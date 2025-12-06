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
use Psr\Log\LoggerInterface;
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
     * @param BlacklistEntryRepository $blacklistEntryRepository The blacklist entry repository
     * @param LoggerInterface          $logger                   The logger service
     * @param int                      $storePeriod              The period to store blacklist entries (in seconds)
     * @param int                      $threshold                The threshold for blacklisting
     * @param int                      $triggerPeriod            The period to consider for blacklisting (in seconds)
     */
    public function __construct(
        private BlacklistEntryRepository $blacklistEntryRepository,
        private LoggerInterface $logger,
        private int $storePeriod,
        private int $threshold,
        private int $triggerPeriod,
    ) {}

    /**
     * Perform cleanup of expired blacklist entries.
     */
    public function cleanup(): void
    {
        $this->logger->debug('Cleaning up blacklist entries');

        $cutoff = Carbon::now()->subSeconds($this->storePeriod);

        $this->blacklistEntryRepository
            ->query([
                'timestamp' => ['<', $cutoff->getTimestamp()],
            ])
            ->delete()
        ;
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
        $this->logger->info('Adding blacklist entry', ['ip' => $ip, 'user_id' => $userId, 'reason' => $reason, 'severity' => $severity]);

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
        $total = 0;

        $total = $this->blacklistEntryRepository
            ->query([
                'timestamp' => ['>', $cutoff],
            ])
            ->when(
                $userId !== null,
                fn ($query) => $query->where([
                    'OR' => [
                        'ip' => $ip,
                        'user_id' => $userId,
                    ],
                ]),
                fn ($query) => $query->where([
                    'ip' => $ip,
                ]),
            )
            ->sum('severity')
        ;

        $isBlacklisted = $total > $this->threshold;

        $this->logger->debug('Is blacklisted', ['ip' => $ip, 'user_id' => $userId, 'is_blacklisted' => $isBlacklisted, 'total' => $total]);

        return $isBlacklisted;
    }
}
