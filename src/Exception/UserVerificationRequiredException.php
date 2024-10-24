<?php

namespace WebFramework\Exception;

use WebFramework\Entity\User;

/**
 * Exception thrown when user verification is required.
 */
class UserVerificationRequiredException extends \Exception
{
    /**
     * UserVerificationRequiredException constructor.
     *
     * @param User $user The user that requires verification
     */
    public function __construct(
        private User $user,
    ) {}

    /**
     * Get the user that requires verification.
     *
     * @return User The user object
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
