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

use WebFramework\Core\StoredUserValuesFactory;
use WebFramework\Entity\User;

/**
 * Manages security iterators for users.
 */
class SecurityIteratorService
{
    /**
     * SecurityIteratorService constructor.
     *
     * @param StoredUserValuesFactory $storedUserValuesFactory The stored user values factory
     */
    public function __construct(
        private StoredUserValuesFactory $storedUserValuesFactory,
    ) {}

    /**
     * Increment the security iterator for a user.
     *
     * @param User $user The user to increment the iterator for
     *
     * @return int The new security iterator value
     */
    public function incrementFor(User $user): int
    {
        $storedValues = $this->storedUserValuesFactory->get($user->getId(), 'account');

        $securityIterator = (int) $storedValues->getValue('security_iterator', '0');
        $securityIterator++;
        $storedValues->setValue('security_iterator', (string) $securityIterator);

        return $securityIterator;
    }

    /**
     * Get the current security iterator for a user.
     *
     * @param User $user The user to get the iterator for
     *
     * @return int The current security iterator value
     */
    public function getFor(User $user): int
    {
        $storedValues = $this->storedUserValuesFactory->get($user->getId(), 'account');

        return (int) $storedValues->getValue('security_iterator', '0');
    }
}
