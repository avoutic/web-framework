<?php

namespace WebFramework\Core\Security;

use WebFramework\Core\User;

class NullAuthenticationService implements AuthenticationService
{
    public function cleanup(): void
    {
    }

    public function is_authenticated(): bool
    {
        return false;
    }

    public function authenticate(User $user): void
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }

    public function deauthenticate(): void
    {
    }

    public function invalidate_sessions(int $user_id): void
    {
    }

    public function get_authenticated_user(): User
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }

    /**
     * @param array<string> $permissions
     */
    public function user_has_permissions(array $permissions): bool
    {
        return false;
    }
}
