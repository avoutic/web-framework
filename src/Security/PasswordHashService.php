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

/**
 * Class PasswordHashService.
 *
 * Handles password hashing and verification.
 */
class PasswordHashService
{
    /**
     * PasswordHashService constructor.
     *
     * @param RandomProvider $randomProvider The random provider service
     */
    public function __construct(
        private RandomProvider $randomProvider,
    ) {}

    /**
     * Implement the PBKDF2 key derivation function.
     *
     * @param string $algorithm The hash algorithm to use
     * @param string $password  The password to derive from
     * @param string $salt      The salt
     * @param int    $count     The iteration count
     * @param int    $keyLength The length of the derived key
     * @param bool   $rawOutput When set to true, outputs raw binary data. false outputs lowercase hexits.
     *
     * @return string A $keyLength-byte derived key
     */
    public function pbkdf2(string $algorithm, string $password, string $salt, int $count, int $keyLength, bool $rawOutput = false): string
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

    /**
     * Generate a password hash.
     *
     * @param string $password The password to hash
     *
     * @return string The generated password hash
     */
    public function generateHash(string $password): string
    {
        $salt = base64_encode($this->randomProvider->getRandom(24));

        return 'sha256:1000:'.$salt.':'.
                $this->pbkdf2('sha256', $password, $salt, 1000, 24, false);
    }

    /**
     * Get the stored and calculated hashes for a password.
     *
     * @param string $passwordHash The stored password hash
     * @param string $password     The password to check
     *
     * @return array{stored: string, calculated: string} The stored and calculated hashes
     *
     * @throws \InvalidArgumentException If the hash format is invalid or unknown
     */
    private function getHashes(string $passwordHash, string $password): array
    {
        $params = explode(':', $passwordHash);

        if ($params[0] == 'sha256')
        {
            if (count($params) !== 4)
            {
                throw new \InvalidArgumentException('sha256 hash format mismatch');
            }

            return [
                'stored' => $params[3],
                'calculated' => $this->pbkdf2(
                    'sha256',
                    $password,
                    $params[2],
                    (int) $params[1],
                    (int) (strlen($params[3]) / 2),
                    false
                ),
            ];
        }

        if ($params[0] == 'bootstrap')
        {
            if (count($params) !== 2)
            {
                throw new \InvalidArgumentException('Bootstrap hash format mismatch');
            }

            return [
                'stored' => $params[1],
                'calculated' => $password,
            ];
        }

        if ($params[0] == 'dolphin')
        {
            if (count($params) !== 3)
            {
                throw new \InvalidArgumentException('Dolphin hash format mismatch');
            }

            return [
                'stored' => $params[2],
                'calculated' => sha1(md5($password).$params[1]),
            ];
        }

        throw new \InvalidArgumentException('Unknown password hash format');
    }

    /**
     * Check if a password matches a hash.
     *
     * @param string $passwordHash The stored password hash
     * @param string $password     The password to check
     *
     * @return bool True if the password matches, false otherwise
     */
    public function checkPassword(string $passwordHash, string $password): bool
    {
        $hashed = $this->getHashes($passwordHash, $password);

        // Slow compare (time-constant)
        $diff = strlen($hashed['stored']) ^ strlen($hashed['calculated']);
        for ($i = 0; $i < strlen($hashed['stored']) && $i < strlen($hashed['calculated']); $i++)
        {
            $diff |= ord($hashed['stored'][$i]) ^ ord($hashed['calculated'][$i]);
        }

        return ($diff === 0);
    }

    /**
     * Check if a password hash should be migrated to a newer format.
     *
     * @param string $passwordHash The stored password hash
     *
     * @return bool True if the hash should be migrated, false otherwise
     *
     * @throws \InvalidArgumentException If the hash format is unknown
     */
    public function shouldMigrate(string $passwordHash): bool
    {
        $params = explode(':', $passwordHash);

        if ($params[0] == 'sha256')
        {
            return false;
        }

        if ($params[0] == 'bootstrap')
        {
            return true;
        }

        if ($params[0] == 'dolphin')
        {
            return true;
        }

        throw new \InvalidArgumentException('Unknown password hash format');
    }
}
