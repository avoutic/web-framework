<?php

namespace WebFramework\Core;

class BaseFactory
{
    public function __construct(
        protected Database $database,
    ) {
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function getUser(int $userId, string $type = User::class): User|false
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::getObjectById($userId);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return array<T>
     */
    public function getUsers(int $offset = 0, int $results = 10, string $type = User::class): array
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::getObjects($offset, $results);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function getUserByUsername(string $username, string $type = User::class): User|false
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::getObject(['username' => $username]);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function getUserByEmail(string $email, string $type = User::class): User|false
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::getObject(['email' => $email]);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return array<T>
     */
    public function searchUsers(string $string, string $type = User::class): array
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

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
            $user = $this->getUser($row['id'], $type);

            if ($user === false)
            {
                throw new \RuntimeException('Failed to retrieve user');
            }

            $data[] = $user;
        }

        return $data;
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return T
     */
    public function createUser(string $username, string $password, string $email, int $termsAccepted, string $type = User::class): User
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        $solidPassword = User::newHashFromPassword($password);

        return $type::create([
            'username' => $username,
            'solid_password' => $solidPassword,
            'email' => $email,
            'terms_accepted' => $termsAccepted,
            'registered' => time(),
        ]);
    }
}
