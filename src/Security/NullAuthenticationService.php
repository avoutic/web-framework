<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use WebFramework\Entity\User;

/**
 * Class NullAuthenticationService.
 *
 * A null implementation of the AuthenticationService interface.
 * This class is useful for testing or when authentication is not required.
 */
class NullAuthenticationService implements AuthenticationService
{
    /**
     * Perform cleanup operations for the authentication service.
     */
    public function cleanup(): void {}

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool Always returns false
     */
    public function isAuthenticated(): bool
    {
        return false;
    }

    /**
     * Authenticate a user.
     *
     * @param User $user The user to authenticate
     *
     * @throws \RuntimeException Always throws an exception as authentication is not supported
     */
    public function authenticate(User $user): void
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }

    /**
     * Deauthenticate the current user.
     */
    public function deauthenticate(): void {}

    /**
     * Invalidate all sessions for a specific user.
     *
     * @param int $userId The ID of the user whose sessions should be invalidated
     */
    public function invalidateSessions(int $userId): void {}

    /**
     * Get the ID of the currently authenticated user.
     *
     * @throws \RuntimeException Always throws an exception as authentication is not supported
     */
    public function getAuthenticatedUserId(): int
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }

    /**
     * Get the currently authenticated user.
     *
     * @throws \RuntimeException Always throws an exception as authentication is not supported
     */
    public function getAuthenticatedUser(): User
    {
        throw new \RuntimeException('Cannot authenticate in null mode');
    }
}
