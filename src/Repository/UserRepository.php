<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Repository;

use WebFramework\Entity\EntityCollection;
use WebFramework\Entity\User;

/**
 * Repository class for User entities.
 *
 * @extends RepositoryCore<User>
 */
class UserRepository extends RepositoryCore
{
    /** @var class-string<User> The entity class associated with this repository */
    protected static string $entityClass = User::class;

    /**
     * Get a User entity by username.
     *
     * @param string $username The username to search for
     *
     * @return null|User The User entity if found, null otherwise
     */
    public function getUserByUsername(string $username): ?User
    {
        return $this->query()
            ->where(['username' => $username])
            ->getOne()
        ;
    }

    /**
     * Get a User entity by email address.
     *
     * @param string $email The email address to search for
     *
     * @return null|User The User entity if found, null otherwise
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->query()
            ->where(['email' => $email])
            ->getOne()
        ;
    }

    /**
     * Search for users based on a string.
     *
     * @param string $string The search string
     *
     * @return EntityCollection<User> A collection of matching User entities
     *
     * @throws \RuntimeException If a user retrieval fails
     */
    public function searchUsers(string $string): EntityCollection
    {
        return $this->query()->where([
            'OR' => [
                'id' => $string,
                'username' => ['LIKE', "%{$string}%"],
                'email' => ['LIKE', "%{$string}%"],
            ],
        ])->execute();
    }
}
