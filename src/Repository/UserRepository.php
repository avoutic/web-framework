<?php

namespace WebFramework\Repository;

use WebFramework\Core\EntityCollection;
use WebFramework\Core\RepositoryCore;
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
        return $this->getObject(['username' => $username]);
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
        return $this->getObject(['email' => $email]);
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
        $query = <<<'SQL'
        SELECT id
        FROM users
        WHERE id = ? OR
              username LIKE ? OR
              email LIKE ?
SQL;

        $result = $this->database->query($query, [
            $string,
            "%{$string}%",
            "%{$string}%",
        ], 'Failed to search users');

        $data = [];
        foreach ($result as $row)
        {
            $user = $this->getObjectById($row['id']);

            if ($user === null)
            {
                throw new \RuntimeException('Failed to retrieve user');
            }

            $data[] = $user;
        }

        return new EntityCollection($data);
    }
}
