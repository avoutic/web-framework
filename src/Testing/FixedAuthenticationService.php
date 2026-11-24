<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Testing;

use WebFramework\Entity\User;
use WebFramework\Security\AuthenticationService;

/**
 * Class FixedAuthenticationService.
 *
 * An AuthenticationService for tests: logged in as the user it is given, or as nobody.
 * It keeps the user in memory and uses no session and no database.
 *
 * Bind it in the definitions of your tests, so the middlewares and services of the framework
 * see the request as coming from that user.
 */
class FixedAuthenticationService implements AuthenticationService
{
    /**
     * FixedAuthenticationService constructor.
     *
     * @param null|User $user The user that is logged in, or null for nobody
     */
    public function __construct(
        private ?User $user = null,
    ) {}

    public function cleanup(): void {}

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    public function authenticate(User $user): void
    {
        $this->user = $user;
    }

    public function deauthenticate(): void
    {
        $this->user = null;
    }

    public function invalidateSessions(int $userId): void
    {
        if ($this->user !== null && $this->user->getId() === $userId)
        {
            $this->user = null;
        }
    }

    public function getAuthenticatedUserId(): int
    {
        return $this->getAuthenticatedUser()->getId();
    }

    public function getAuthenticatedUser(): User
    {
        if ($this->user === null)
        {
            throw new \RuntimeException('Not authenticated');
        }

        return $this->user;
    }
}
