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
 * Exception thrown when a login extension refuses the login.
 */
class LoginRefusedException extends \Exception
{
    /**
     * LoginRefusedException constructor.
     *
     * @param User $user The user whose login was refused
     */
    public function __construct(
        private User $user,
    ) {}

    /**
     * Get the user whose login was refused.
     *
     * @return User The user object
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
