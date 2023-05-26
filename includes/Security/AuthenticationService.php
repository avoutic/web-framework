<?php

namespace WebFramework\Security;

use WebFramework\Core\User;

interface AuthenticationService
{
    public function cleanup(): void;

    public function isAuthenticated(): bool;

    public function authenticate(User $user): void;

    public function deauthenticate(): void;

    public function invalidateSessions(int $userId): void;

    public function getAuthenticatedUser(): User;

    /**
     * @param array<string> $permissions
     */
    public function userHasPermissions(array $permissions): bool;
}
