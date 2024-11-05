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

use WebFramework\Entity\User;

/**
 * Interface AuthenticationService.
 *
 * Defines the contract for authentication services in the WebFramework.
 */
interface AuthenticationService
{
    /**
     * Perform cleanup operations for the authentication service.
     */
    public function cleanup(): void;

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool True if a user is authenticated, false otherwise
     */
    public function isAuthenticated(): bool;

    /**
     * Authenticate a user.
     *
     * @param User $user The user to authenticate
     */
    public function authenticate(User $user): void;

    /**
     * Deauthenticate the current user.
     */
    public function deauthenticate(): void;

    /**
     * Invalidate all sessions for a specific user.
     *
     * @param int $userId The ID of the user whose sessions should be invalidated
     */
    public function invalidateSessions(int $userId): void;

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int The ID of the authenticated user
     *
     * @throws \RuntimeException If no user is authenticated
     */
    public function getAuthenticatedUserId(): int;

    /**
     * Get the currently authenticated user.
     *
     * @return User The authenticated user
     *
     * @throws \RuntimeException If no user is authenticated or the user cannot be found
     */
    public function getAuthenticatedUser(): User;
}
