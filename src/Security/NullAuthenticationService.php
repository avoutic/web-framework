<?php

namespace WebFramework\Security;

use WebFramework\Entity\User;

class NullAuthenticationService implements AuthenticationService
{
    public function cleanup(): void {}

    public function isAuthenticated(): bool
    {
        return false;
    }

    public function authenticate(User $user): void
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }

    public function deauthenticate(): void {}

    public function invalidateSessions(int $userId): void {}

    public function getAuthenticatedUserId(): int
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }

    public function getAuthenticatedUser(): User
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }
}
