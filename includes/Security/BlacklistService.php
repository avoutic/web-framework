<?php

namespace WebFramework\Security;

interface BlacklistService
{
    public function addEntry(string $ip, ?int $userId, string $reason, int $severity = 1): void;

    public function isBlacklisted(string $ip, ?int $userId): bool;
}
