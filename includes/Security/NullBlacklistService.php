<?php

namespace WebFramework\Security;

class NullBlacklistService implements BlacklistService
{
    public function add_entry(string $ip, ?int $user_id, string $reason, int $severity = 1): void
    {
    }

    public function is_blacklisted(string $ip, ?int $user_id): bool
    {
        return false;
    }
}
