<?php

namespace WebFramework\Security;

class NullBlacklistService implements BlacklistService
{
    public function addEntry(string $ip, ?int $userId, string $reason, int $severity = 1): void
    {
    }

    public function isBlacklisted(string $ip, ?int $userId): bool
    {
        return false;
    }
}
