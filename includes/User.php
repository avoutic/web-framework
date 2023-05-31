<?php

namespace WebFramework\Core;

function pbkdf2(string $algorithm, string $password, string $salt, int $count, int $keyLength, bool $rawOutput = false): string
{
    $algorithm = strtolower($algorithm);
    if (!in_array($algorithm, hash_algos(), true))
    {
        exit('PBKDF2 ERROR: Invalid hash algorithm.');
    }
    if ($count <= 0 || $keyLength <= 0)
    {
        exit('PBKDF2 ERROR: Invalid parameters.');
    }

    $hashLength = strlen(hash($algorithm, '', true));
    $blockCount = ceil($keyLength / $hashLength);

    $output = '';
    for ($i = 1; $i <= $blockCount; $i++)
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

    if ($rawOutput)
    {
        return substr($output, 0, $keyLength);
    }

    return bin2hex(substr($output, 0, $keyLength));
}

class User extends DataCore
{
    // Error messages
    //
    public const RESULT_SUCCESS = 0;
    public const ERR_DUPLICATE_EMAIL = 1;
    public const ERR_ORIG_PASSWORD_MISMATCH = 2;
    public const ERR_NEW_PASSWORD_TOO_WEAK = 3;

    protected static string $tableName = 'users';
    protected static array $baseFields = ['username', 'email', 'terms_accepted', 'verified', 'last_login', 'failed_login'];

    /**
     * @var array<Right>
     */
    public array $rights = [];

    /**
     * @var array<string, StoredUserValues>
     */
    protected array $storedValues;
    protected ?UserMailer $userMailer = null;

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

    protected function fillComplexFields(): void
    {
        $userRights = UserRight::getObjects(0, -1, ['user_id' => $this->id]);

        foreach ($userRights as $userRight)
        {
            $right = $userRight->getRight();
            if ($right === false)
            {
                throw new \RuntimeException('Failed to retrieve right');
            }

            $this->rights[$right->shortName] = $right;
        }
    }

    protected function userMailer(): UserMailer
    {
        if ($this->userMailer === null)
        {
            $this->userMailer = $this->container->get(UserMailer::class);
        }

        return $this->userMailer;
    }

    public static function newHashFromPassword(string $password): string
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
    protected function getCustomHash(array $params, string $password): false|array
    {
        return false;
    }

    public function checkPassword(string $password): bool
    {
        $solidPassword = $this->getField('solid_password');
        $storedHash = 'stored';
        $calculatedHash = 'calculated';

        $params = explode(':', $solidPassword);
        $migratePassword = false;

        if ($params[0] == 'sha256')
        {
            if (count($params) !== 4)
            {
                throw new \InvalidArgumentException('Solid password format unknown');
            }

            $storedHash = $params[3];
            $calculatedHash = pbkdf2(
                'sha256',
                $password,
                $params[2],
                (int) $params[1],
                (int) (strlen($storedHash) / 2),
                false
            );
        }
        elseif ($params[0] == 'bootstrap')
        {
            if (count($params) !== 2)
            {
                throw new \InvalidArgumentException('Solid password format unknown');
            }

            $storedHash = $params[1];
            $calculatedHash = $password;
            $migratePassword = true;
        }
        elseif ($params[0] == 'dolphin')
        {
            if (count($params) !== 3)
            {
                throw new \InvalidArgumentException('Solid password format unknown');
            }

            $storedHash = $params[2];
            $calculatedHash = sha1(md5($password).$params[1]);
            $migratePassword = true;
        }
        else
        {
            $result = $this->getCustomHash($params, $password);
            if ($result === false)
            {
                throw new \InvalidArgumentException('Unknown solid password format');
            }
            if (!isset($result['stored_hash']))
            {
                throw new \RuntimeException('Invalid result from get_custom_hash');
            }
            if (!isset($result['calculated_hash']))
            {
                throw new \RuntimeException('Invalid result from get_custom_hash');
            }

            $storedHash = $result['stored_hash'];
            $calculatedHash = $result['calculated_hash'];
            $migratePassword = true;
        }

        // Slow compare (time-constant)
        $diff = strlen($storedHash) ^ strlen($calculatedHash);
        for ($i = 0; $i < strlen($storedHash) && $i < strlen($calculatedHash); $i++)
        {
            $diff |= ord($storedHash[$i]) ^ ord($calculatedHash[$i]);
        }

        $result = ($diff === 0);

        if ($result)
        {
            if ($migratePassword)
            {
                $solidPassword = self::newHashFromPassword($password);
                $this->updateField('solid_password', $solidPassword);
            }

            $this->update([
                'failed_login' => 0,
                'last_login' => time(),
            ]);
        }
        else
        {
            $this->increaseField('failed_login');
        }

        return $result;
    }

    public function changePassword(string $oldPassword, string $newPassword): int
    {
        // Check if original password is correct
        //
        if ($this->checkPassword($oldPassword) !== true)
        {
            return self::ERR_ORIG_PASSWORD_MISMATCH;
        }

        if (strlen($newPassword) < 8)
        {
            return self::ERR_NEW_PASSWORD_TOO_WEAK;
        }

        // Change password
        //
        $solidPassword = self::newHashFromPassword($newPassword);
        $this->updateField('solid_password', $solidPassword);

        return self::RESULT_SUCCESS;
    }

    public function updatePassword(string $newPassword): int
    {
        // Change password
        //
        $solidPassword = self::newHashFromPassword($newPassword);

        $this->updateField('solid_password', $solidPassword);

        $securityIterator = $this->increaseSecurityIterator();

        return self::RESULT_SUCCESS;
    }

    public function changeEmail(string $email, bool $requireUnique = true): int
    {
        if ($requireUnique)
        {
            // Check if unique
            //
            $query = <<<'SQL'
            SELECT id
            FROM users
            WHERE LOWER(email) = LOWER(?)
SQL;

            $result = $this->query($query, [$email]);
            if ($result === false)
            {
                throw new \RuntimeException('Failed to search email');
            }

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

        if ($this->getConfig('authenticator.unique_identifier') == 'email')
        {
            $updates['username'] = $email;
        }

        $this->update($updates);

        return self::RESULT_SUCCESS;
    }

    public function sendChangeEmailVerify(string $email, bool $requireUnique = true): false|int
    {
        if ($requireUnique)
        {
            // Check if unique
            //
            $result = $this->query('SELECT id FROM users WHERE LOWER(email) = LOWER(?)', [$email]);
            if ($result === false)
            {
                throw new \RuntimeException('Failed to check email');
            }

            if ($result->RecordCount() > 0)
            {
                return self::ERR_DUPLICATE_EMAIL;
            }
        }

        $securityIterator = $this->increaseSecurityIterator();

        $code = $this->generateVerifyCode('change_email', ['email' => $email, 'iterator' => $securityIterator]);
        $verifyUrl = $this->getConfig('http_mode').'://'.$this->container->get('server_name').
                      $this->getConfig('base_url').
                      $this->getConfig('actions.change_email.verify_page').
                      '?code='.$code;

        $result = $this->userMailer()->changeEmailVerificationLink(
            $email,
            [
                'user' => $this,
                'verify_url' => $verifyUrl,
            ]
        );
        if ($result == true)
        {
            return self::RESULT_SUCCESS;
        }

        return false;
    }

    public function isVerified(): bool
    {
        return $this->verified == 1;
    }

    public function setVerified(): void
    {
        $this->updateField('verified', 1);
    }

    public function addRight(string $shortName): void
    {
        if (isset($this->rights[$shortName]))
        {
            return;
        }

        $right = Right::getObject(['short_name' => $shortName]);
        if ($right === false)
        {
            throw new \RuntimeException('Failed to locate right');
        }

        UserRight::create([
            'user_id' => $this->id,
            'right_id' => $right->id,
        ]);

        $this->rights[$shortName] = $right;
    }

    public function deleteRight(string $shortName): void
    {
        if (!isset($this->rights[$shortName]))
        {
            return;
        }

        $right = Right::getObject(['short_name' => $shortName]);
        if ($right === false)
        {
            throw new \RuntimeException('Failed to locate right');
        }

        $userRight = UserRight::getObject([
            'user_id' => $this->id,
            'right_id' => $right->id,
        ]);

        if ($userRight !== false)
        {
            $userRight->delete();
            unset($this->rights[$shortName]);
        }
    }

    public function hasRight(string $shortName): bool
    {
        return isset($this->rights[$shortName]);
    }

    /**
     * @param array<mixed> $params
     */
    public function generateVerifyCode(string $action = '', array $params = []): string
    {
        $msg = ['id' => $this->id,
            'username' => $this->username,
            'action' => $action,
            'params' => $params,
            'timestamp' => time(), ];

        return $this->encodeAndAuthArray($msg);
    }

    /**
     * @param array<mixed> $afterVerifyData
     */
    public function sendVerifyMail(array $afterVerifyData = []): bool|string
    {
        $code = $this->generateVerifyCode('verify', $afterVerifyData);
        $verifyUrl = $this->getConfig('http_mode').'://'.$this->container->get('server_name').
                      $this->getConfig('base_url').
                      $this->getConfig('actions.login.verify_page').
                      '?code='.$code;

        return $this->userMailer()->emailVerificationLink(
            $this->email,
            [
                'user' => $this,
                'verify_url' => $verifyUrl,
            ]
        );
    }

    protected function increaseSecurityIterator(): int
    {
        $storedValues = $this->getStoredValues('account');

        $securityIterator = (int) $storedValues->getValue('security_iterator', '0');
        $securityIterator++;
        $storedValues->setValue('security_iterator', (string) $securityIterator);

        return $securityIterator;
    }

    public function getSecurityIterator(): int
    {
        $storedValues = $this->getStoredValues('account');

        return (int) $storedValues->getValue('security_iterator', '0');
    }

    public function sendPasswordResetMail(): bool|string
    {
        $securityIterator = $this->increaseSecurityIterator();

        $code = $this->generateVerifyCode('reset_password', ['iterator' => $securityIterator]);
        $resetUrl = $this->getConfig('http_mode').'://'.$this->container->get('server_name').
                     $this->getConfig('base_url').
                     $this->getConfig('actions.forgot_password.reset_password_page').
                     '?code='.$code;

        return $this->userMailer()->passwordReset(
            $this->email,
            [
                'user' => $this,
                'reset_url' => $resetUrl,
            ]
        );
    }

    public function sendNewPassword(): bool|string
    {
        // Generate and store password
        //
        $newPw = bin2hex(substr(openssl_random_pseudo_bytes(24), 0, 10));

        $this->updatePassword($newPw);

        return $this->userMailer()->newPassword(
            $this->email,
            [
                'user' => $this,
                'password' => $newPw,
            ]
        );
    }

    public function getStoredValues(string $module): StoredUserValues
    {
        if (!isset($this->storedValues[$module]))
        {
            $this->storedValues[$module] = new StoredUserValues($this->database, $this->id, $module);
        }

        return $this->storedValues[$module];
    }
}
