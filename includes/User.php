<?php

namespace WebFramework\Core;

function pbkdf2(string $algorithm, string $password, string $salt, int $count, int $key_length, bool $raw_output = false): string
{
    $algorithm = strtolower($algorithm);
    if (!in_array($algorithm, hash_algos(), true))
    {
        exit('PBKDF2 ERROR: Invalid hash algorithm.');
    }
    if ($count <= 0 || $key_length <= 0)
    {
        exit('PBKDF2 ERROR: Invalid parameters.');
    }

    $hash_length = strlen(hash($algorithm, '', true));
    $block_count = ceil($key_length / $hash_length);

    $output = '';
    for ($i = 1; $i <= $block_count; $i++)
    {
        // $i encoded as 4 bytes, big endian.
        $last = $salt.pack('N', $i);
        // first iteration
        $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
        // perform the other $count - 1 iterations
        for ($j = 1; $j < $count; $j++)
        {
            $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
        }
        $output .= $xorsum;
    }

    if ($raw_output)
    {
        return substr($output, 0, $key_length);
    }

    return bin2hex(substr($output, 0, $key_length));
}

class User extends DataCore
{
    // Error messages
    //
    public const RESULT_SUCCESS = 0;
    public const ERR_DUPLICATE_EMAIL = 1;
    public const ERR_ORIG_PASSWORD_MISMATCH = 2;
    public const ERR_NEW_PASSWORD_TOO_WEAK = 3;

    protected static string $table_name = 'users';
    protected static array $base_fields = ['username', 'email', 'terms_accepted', 'verified', 'last_login', 'failed_login'];

    public string $username;
    public string $email;
    public int $terms_accepted;
    public bool $verified;
    public int $last_login;
    public int $failed_login;

    /**
     * @var array<Right>
     */
    public array $rights = [];

    /**
     * @var array<string, StoredUserValues>
     */
    protected array $stored_values;

    /**
     * @return array<string>
     */
    public function __serialize(): array
    {
        $arr = parent::__serialize();

        return array_merge($arr, [
            'rights' => serialize($this->rights),
        ]);
    }

    /**
     * @param array<string> $data
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->rights = unserialize($data['rights']);
    }

    protected function fill_complex_fields(): void
    {
        $user_rights = UserRight::get_objects(0, -1, ['user_id' => $this->id]);

        foreach ($user_rights as $user_right)
        {
            $right = $user_right->get_right();
            $this->verify($right !== false, 'Failed to retrieve right');

            $this->rights[$right->short_name] = $right;
        }
    }

    public static function new_hash_from_password(string $password): string
    {
        $salt = base64_encode(openssl_random_pseudo_bytes(24));

        return 'sha256:1000:'.$salt.':'.
                pbkdf2('sha256', $password, $salt, 1000, 24, false);
    }

    /**
     * @param array<mixed> $params
     *
     * @return array{calculated_hash: string, stored_hash: string}
     */
    protected function get_custom_hash(array $params, string $password): false|array
    {
        return false;
    }

    public function check_password(string $password): bool
    {
        $solid_password = $this->get_field('solid_password');
        $stored_hash = 'stored';
        $calculated_hash = 'calculated';

        $params = explode(':', $solid_password);
        $migrate_password = false;

        if ($params[0] == 'sha256')
        {
            $this->verify(count($params) == 4, 'Solid password format unknown');

            $stored_hash = $params[3];
            $calculated_hash = pbkdf2(
                'sha256',
                $password,
                $params[2],
                (int) $params[1],
                (int) (strlen($stored_hash) / 2),
                false
            );
        }
        elseif ($params[0] == 'bootstrap')
        {
            $this->verify(count($params) == 2, 'Solid password format unknown');

            $stored_hash = $params[1];
            $calculated_hash = $password;
            $migrate_password = true;
        }
        elseif ($params[0] == 'dolphin')
        {
            $this->verify(count($params) == 3, 'Solid password format unknown');

            $stored_hash = $params[2];
            $calculated_hash = sha1(md5($password).$params[1]);
            $migrate_password = true;
        }
        else
        {
            $result = $this->get_custom_hash($params, $password);
            $this->verify($result !== false, 'Unknown solid password format');
            $this->verify(isset($result['stored_hash']), 'Invalid result from get_custom_hash');
            $this->verify(isset($result['calculated_hash']), 'Invalid result from get_custom_hash');

            $stored_hash = $result['stored_hash'];
            $calculated_hash = $result['calculated_hash'];
            $migrate_password = true;
        }

        // Slow compare (time-constant)
        $diff = strlen($stored_hash) ^ strlen($calculated_hash);
        for ($i = 0; $i < strlen($stored_hash) && $i < strlen($calculated_hash); $i++)
        {
            $diff |= ord($stored_hash[$i]) ^ ord($calculated_hash[$i]);
        }

        $result = ($diff === 0);

        if ($result)
        {
            if ($migrate_password)
            {
                $solid_password = self::new_hash_from_password($password);
                $this->update_field('solid_password', $solid_password);
            }

            $this->update([
                'failed_login' => 0,
                'last_login' => time(),
            ]);
        }
        else
        {
            $this->increase_field('failed_login');
        }

        return $result;
    }

    public function change_password(string $old_password, string $new_password): int
    {
        // Check if original password is correct
        //
        if ($this->check_password($old_password) !== true)
        {
            return self::ERR_ORIG_PASSWORD_MISMATCH;
        }

        if (strlen($new_password) < 8)
        {
            return self::ERR_NEW_PASSWORD_TOO_WEAK;
        }

        // Change password
        //
        $solid_password = self::new_hash_from_password($new_password);
        $this->update_field('solid_password', $solid_password);

        return self::RESULT_SUCCESS;
    }

    public function update_password(string $new_password): int
    {
        // Change password
        //
        $solid_password = self::new_hash_from_password($new_password);

        $this->update_field('solid_password', $solid_password);

        $security_iterator = $this->increase_security_iterator();

        return self::RESULT_SUCCESS;
    }

    public function change_email(string $email, bool $require_unique = true): int
    {
        if ($require_unique)
        {
            // Check if unique
            //
            $query = <<<'SQL'
            SELECT id
            FROM users
            WHERE LOWER(email) = LOWER(?)
SQL;

            $result = $this->query($query, [$email]);
            $this->verify($result !== false, 'Failed to search email');

            if ($result->RecordCount() > 0)
            {
                return self::ERR_DUPLICATE_EMAIL;
            }
        }

        // Update account
        //
        $updates = [
            'email' => $email,
        ];

        if ($this->get_config('authenticator.unique_identifier') == 'email')
        {
            $updates['username'] = $email;
        }

        $this->update($updates);

        return self::RESULT_SUCCESS;
    }

    public function send_change_email_verify(string $email, bool $require_unique = true): false|int
    {
        if ($require_unique)
        {
            // Check if unique
            //
            $result = $this->query('SELECT id FROM users WHERE LOWER(email) = LOWER(?)', [$email]);
            $this->verify($result !== false, 'Failed to check email');

            if ($result->RecordCount() > 0)
            {
                return self::ERR_DUPLICATE_EMAIL;
            }
        }

        $security_iterator = $this->increase_security_iterator();

        $code = $this->generate_verify_code('change_email', ['email' => $email, 'iterator' => $security_iterator]);
        $verify_url = $this->get_config('http_mode').'://'.$this->get_config('server_name').
                      $this->get_config('base_url').
                      $this->get_config('actions.change_email.verify_page').
                      '?code='.$code;

        $result = SenderCore::send(
            'change_email_verification_link',
            $email,
            [
                'user' => $this,
                'verify_url' => $verify_url,
            ]
        );
        if ($result == true)
        {
            return self::RESULT_SUCCESS;
        }

        return false;
    }

    public function is_verified(): bool
    {
        return $this->verified == 1;
    }

    public function set_verified(): void
    {
        $this->update_field('verified', 1);
    }

    public function add_right(string $short_name): void
    {
        if (isset($this->rights[$short_name]))
        {
            return;
        }

        $right = Right::get_object(['short_name' => $short_name]);
        $this->verify($right !== false, 'Failed to locate right');

        UserRight::create([
            'user_id' => $this->id,
            'right_id' => $right->id,
        ]);

        $this->rights[$short_name] = $right;
    }

    public function delete_right(string $short_name): void
    {
        if (!isset($this->rights[$short_name]))
        {
            return;
        }

        $right = Right::get_object(['short_name' => $short_name]);
        $this->verify($right !== false, 'Failed to locate right');

        $user_right = UserRight::get_object([
            'user_id' => $this->id,
            'right_id' => $right->id,
        ]);

        if ($user_right !== false)
        {
            $user_right->delete();
            unset($this->rights[$short_name]);
        }
    }

    public function has_right(string $short_name): bool
    {
        return isset($this->rights[$short_name]);
    }

    /**
     * @param array<mixed> $params
     */
    public function generate_verify_code(string $action = '', array $params = []): string
    {
        $msg = ['id' => $this->id,
            'username' => $this->username,
            'action' => $action,
            'params' => $params,
            'timestamp' => time(), ];

        return $this->encode_and_auth_array($msg);
    }

    /**
     * @param array<mixed> $after_verify_data
     */
    public function send_verify_mail(array $after_verify_data = []): bool|string
    {
        $code = $this->generate_verify_code('verify', $after_verify_data);
        $verify_url = $this->get_config('http_mode').'://'.$this->get_config('server_name').
                      $this->get_config('base_url').
                      $this->get_config('actions.login.verify_page').
                      '?code='.$code;

        return SenderCore::send(
            'email_verification_link',
            $this->email,
            [
                'user' => $this,
                'verify_url' => $verify_url,
            ]
        );
    }

    protected function increase_security_iterator(): int
    {
        $stored_values = $this->get_stored_values('account');

        $security_iterator = (int) $stored_values->get_value('security_iterator', '0');
        $security_iterator++;
        $stored_values->set_value('security_iterator', (string) $security_iterator);

        return $security_iterator;
    }

    public function get_security_iterator(): int
    {
        $stored_values = $this->get_stored_values('account');

        return (int) $stored_values->get_value('security_iterator', '0');
    }

    public function send_password_reset_mail(): bool|string
    {
        $security_iterator = $this->increase_security_iterator();

        $code = $this->generate_verify_code('reset_password', ['iterator' => $security_iterator]);
        $reset_url = $this->get_config('http_mode').'://'.$this->get_config('server_name').
                     $this->get_config('base_url').
                     $this->get_config('actions.forgot_password.reset_password_page').
                     '?code='.$code;

        return SenderCore::send(
            'password_reset',
            $this->email,
            [
                'user' => $this,
                'reset_url' => $reset_url,
            ]
        );
    }

    public function send_new_password(): bool|string
    {
        // Generate and store password
        //
        $new_pw = bin2hex(substr(openssl_random_pseudo_bytes(24), 0, 10));

        $this->update_password($new_pw);

        return SenderCore::send(
            'new_password',
            $this->email,
            [
                'user' => $this,
                'password' => $new_pw,
            ]
        );
    }

    public function get_stored_values(string $module): StoredUserValues
    {
        if (!isset($this->stored_values[$module]))
        {
            $this->stored_values[$module] = new StoredUserValues($this->database, $this->id, $module);
        }

        return $this->stored_values[$module];
    }
}
