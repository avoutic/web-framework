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
    public function get_user(int $user_id, string $type = User::class): User|false
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::get_object_by_id($user_id);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return array<T>
     */
    public function get_users(int $offset = 0, int $results = 10, string $type = User::class): array
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::get_objects($offset, $results);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function get_user_by_username(string $username, string $type = User::class): User|false
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::get_object(['username' => $username]);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function get_user_by_email(string $email, string $type = User::class): User|false
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        return $type::get_object(['email' => $email]);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return array<T>
     */
    public function search_users(string $string, string $type = User::class): array
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
            $user = $this->get_user($row['id'], $type);

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
    public function create_user(string $username, string $password, string $email, int $terms_accepted, string $type = User::class): User
    {
        if (!class_exists($type))
        {
            throw new \InvalidArgumentException("Class {$type} does not exist");
        }

        $solid_password = User::new_hash_from_password($password);

        return $type::create([
            'username' => $username,
            'solid_password' => $solid_password,
            'email' => $email,
            'terms_accepted' => $terms_accepted,
            'registered' => time(),
        ]);
    }
}
