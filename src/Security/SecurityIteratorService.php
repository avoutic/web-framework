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

use Psr\Log\LoggerInterface;
use WebFramework\Entity\User;
use WebFramework\Support\StoredUserValuesService;

/**
 * Manages security iterators for users.
 */
class SecurityIteratorService
{
    /**
     * SecurityIteratorService constructor.
     *
     * @param LoggerInterface         $logger                  The logger service
     * @param StoredUserValuesService $storedUserValuesService The stored user values service
     */
    public function __construct(
        private LoggerInterface $logger,
        private StoredUserValuesService $storedUserValuesService,
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
        $securityIterator = (int) $this->storedUserValuesService->getValue(
            $user->getId(),
            'account.security_iterator',
            '0',
        );

        $securityIterator++;

        $this->logger->info('Incrementing security iterator for user', ['user_id' => $user->getId(), 'new_security_iterator' => $securityIterator]);

        $this->storedUserValuesService->setValue(
            $user->getId(),
            'account.security_iterator',
            (string) $securityIterator,
        );

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
        return (int) $this->storedUserValuesService->getValue(
            $user->getId(),
            'account.security_iterator',
            '0',
        );
    }
}
