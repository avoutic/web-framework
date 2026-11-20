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
 * Default implementation of AccountStatusChecker.
 *
 * Follows the framework convention that an empty password hash marks a blocked account.
 */
class DefaultAccountStatusChecker implements AccountStatusChecker
{
    public function isEnabled(User $user): bool
    {
        return $user->getSolidPassword() !== '';
    }
}
