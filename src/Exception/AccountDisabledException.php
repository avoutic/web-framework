<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Exception;

use WebFramework\Entity\User;

/**
 * Exception thrown when an operation is refused because the account is disabled.
 */
class AccountDisabledException extends \Exception
{
    /**
     * AccountDisabledException constructor.
     *
     * @param User $user The user whose account is disabled
     */
    public function __construct(
        private User $user,
    ) {}

    /**
     * Get the user whose account is disabled.
     *
     * @return User The user object
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
