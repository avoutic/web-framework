<?php

namespace WebFramework\Exception;

use WebFramework\Entity\User;

class UserVerificationRequiredException extends \Exception
{
    public function __construct(
        protected User $user,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
