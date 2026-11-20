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
 * Null implementation of AccountStatusChecker.
 *
 * Treats every account as enabled.
 */
class NullAccountStatusChecker implements AccountStatusChecker
{
    public function isEnabled(User $user): bool
    {
        return true;
    }
}
