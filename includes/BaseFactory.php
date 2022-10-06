<?php

namespace WebFramework\Core;

class BaseFactory extends FactoryCore
{
    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function get_user(int $user_id, string $type = '\\WebFramework\\Core\\User'): User|false
    {
        $this->verify(class_exists($type), 'Class does not exist');

        return $type::get_object_by_id($user_id);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return array<T>
     */
    public function get_users(int $offset = 0, int $results = 10, string $type = '\\WebFramework\\Core\\User'): array
    {
        $this->verify(class_exists($type), 'Class does not exist');

        return $type::get_objects($offset, $results);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function get_user_by_username(string $username, string $type = '\\WebFramework\\Core\\User'): User|false
    {
        $this->verify(class_exists($type), 'Class does not exist');

        return $type::get_object(['username' => $username]);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return false|T
     */
    public function get_user_by_email(string $email, string $type = '\\WebFramework\\Core\\User'): User|false
    {
        $this->verify(class_exists($type), 'Class does not exist');

        return $type::get_object(['email' => $email]);
    }

    /**
     * @template T of User
     *
     * @param class-string<T> $type
     *
     * @return array<T>
     */
    public function search_users(string $string, string $type = '\\WebFramework\\Core\\User'): array
    {
        $query = <<<'SQL'
        SELECT id
        FROM users
        WHERE id = ? OR
              username LIKE ? OR
              email LIKE ?
SQL;

        $result = $this->query($query, [
            $string,
            "%{$string}%",
            "%{$string}%",
        ]);
        $this->verify($result !== false, 'Failed to search users');

        $data = [];
        foreach ($result as $row)
        {
            $user = $this->get_user($row['id'], $type);
            $this->verify($user !== false, 'Failed to retrieve user');

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
    public function create_user(string $username, string $password, string $email, int $terms_accepted, string $type = '\\WebFramework\\Core\\User'): User
    {
        $this->verify(class_exists($type), 'Class does not exist');

        $solid_password = User::new_hash_from_password($password);

        $user = $type::create([
            'username' => $username,
            'solid_password' => $solid_password,
            'email' => $email,
            'terms_accepted' => $terms_accepted,
            'registered' => time(),
        ]);
        $this->verify($user !== false, 'Failed to create new user');

        return $user;
    }
}
