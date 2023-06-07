<?php

namespace WebFramework\Repository;

use WebFramework\Core\EntityCollection;
use WebFramework\Core\RepositoryCore;
use WebFramework\Entity\User;

/**
 * @extends RepositoryCore<User>
 */
class UserRepository extends RepositoryCore
{
    protected static string $entityClass = User::class;

    public function getUserByUsername(string $username): ?User
    {
        return $this->getObject(['username' => $username]);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->getObject(['email' => $email]);
    }

    /**
     * @return EntityCollection<User>
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
        ]);

        if ($result === false)
        {
            throw new \RuntimeException('Failed to search users');
        }

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
