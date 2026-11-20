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
 * Interface AccountStatusChecker.
 *
 * Decides whether an account may be used. It is consulted before a login is completed,
 * on every request of a logged in user, before password reset and verification mails
 * are sent, and before a password or email change.
 *
 * Use this for anything that is a property of the account (disabled, suspended, pending
 * approval). For checks that depend on the request, use LoginExtensionInterface::preAuthenticate().
 */
interface AccountStatusChecker
{
    /**
     * Check if the account of a user is enabled.
     *
     * @param User $user The user to check
     *
     * @return bool True if the account may be used, false if it is disabled
     */
    public function isEnabled(User $user): bool;
}
