<?php

namespace WebFramework\Security;

use WebFramework\Entity\User;

interface AuthenticationService
{
    public function cleanup(): void;

    public function isAuthenticated(): bool;

    public function authenticate(User $user): void;

    public function deauthenticate(): void;

    public function invalidateSessions(int $userId): void;

    public function getAuthenticatedUserId(): int;

    public function getAuthenticatedUser(): User;
}
